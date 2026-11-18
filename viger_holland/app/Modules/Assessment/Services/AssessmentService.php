<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\SessionQuestion;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Support\TestConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Làm gì:   Điều phối một lượt làm bài:
 *           start()  bốc đề, lưu 36 câu kèm nội dung và phiên bản câu lúc phát đề, phiên bản cài đặt đang dùng, hạt giống ngẫu nhiên;
 *           finish() nhận câu trả lời, kiểm tra đủ và đúng thang 1–5, chấm điểm, gợi ý ngành, lưu kết quả bản nội bộ
 *                    và phần khách được thấy (mã 2 chữ, chữ thứ 3 khi đủ điều kiện, tối đa N ngành, chỉ nhãn chữ).
 *           Kết quả cũ không đổi khi admin sửa câu hỏi hay cài đặt sau đó.
 * Ai gọi:   trang làm bài của khách (H3), test.
 * Dữ liệu:  bảng test_sessions, session_questions, responses; đọc questions, majors, riasec_pairs, TestConfig.
 */
final class AssessmentService
{
    /**
     * @param  array<string, string>|null  $utm
     */
    public function start(?string $deviceHash = null, ?array $utm = null, string $source = 'holland'): TestSession
    {
        $settings = TestConfig::current();
        $seed = random_int(1, PHP_INT_MAX);
        $draw = (new QuestionPicker($settings, $seed))->pick($this->previousCodes($deviceHash));

        return DB::transaction(function () use ($draw, $deviceHash, $utm, $source): TestSession {
            $session = TestSession::create([
                'uuid' => (string) Str::uuid(),
                'result_token' => (string) Str::uuid(),
                'test_config_version_id' => TestConfig::currentVersion()?->id,
                'status' => 'in_progress',
                'device_hash' => $deviceHash,
                'generation_seed' => (string) $draw['seed'],
                'generation_warnings' => $draw['warnings'] ?: null,
                'source' => $source,
                'utm' => $utm,
                'started_at' => now(),
            ]);

            foreach ($draw['questions']->values() as $index => $question) {
                SessionQuestion::create([
                    'test_session_id' => $session->id,
                    'question_id' => $question->id,
                    'group' => $question->group,
                    'item_version' => $question->item_version,
                    'text_snapshot' => $question->text,
                    'display_order' => $index + 1,
                ]);
            }

            return $session;
        });
    }

    /**
     * @param  array<int, array{value: int, ms?: int|null}>  $answers  question_id => câu trả lời
     *
     * @throws InvalidArgumentException khi thiếu câu, thừa câu hoặc giá trị ngoài 1–5
     */
    public function finish(TestSession $session, array $answers): TestSession
    {
        $items = $session->sessionQuestions()->with('question')->orderBy('display_order')->get();
        $expected = $items->pluck('question_id')->sort()->values()->all();
        $given = collect(array_keys($answers))->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($expected !== $given) {
            throw new InvalidArgumentException('Câu trả lời không khớp với đề của lượt làm bài.');
        }
        foreach ($answers as $answer) {
            if (! is_int($answer['value'] ?? null) || $answer['value'] < 1 || $answer['value'] > 5) {
                throw new InvalidArgumentException('Mỗi câu phải chọn từ 1 đến 5 sao.');
            }
        }

        $settings = $this->settingsFor($session);

        $ordered = $items->map(fn (SessionQuestion $item) => [
            'code' => $item->question?->code ?? (string) $item->question_id,
            'group' => $item->group,
            'value' => $answers[$item->question_id]['value'],
            'ms' => $answers[$item->question_id]['ms'] ?? null,
        ])->all();

        $result = (new ScoringEngine($settings))->score($ordered);
        $matcher = new MajorMatcher($settings);
        $result['majors'] = $matcher->recommend($result['code3'], $result['groups']);
        $result['customer'] = [
            'code' => $result['third_detail'] ? $result['code3'] : $result['code2'],
            'majors' => $matcher->forCustomer($result['majors']),
        ];

        return DB::transaction(function () use ($session, $answers, $result): TestSession {
            foreach ($answers as $questionId => $answer) {
                $session->responses()->updateOrCreate(['question_id' => $questionId], ['value' => $answer['value'], 'ms' => $answer['ms'] ?? null]);
            }

            $session->update([
                'status' => 'answered',
                'scores' => collect($result['groups'])->map(fn (array $g) => $g['mean'])->all(),
                'result' => $result,
                'code2' => $result['code2'],
                'code3' => $result['code3'],
                'profile_case' => $result['profile']['case'],
                'needs_counseling' => $result['profile']['needs_counseling'],
                'reliability' => $result['quality']['reliability'],
                'answered_at' => now(),
            ]);

            return $session->refresh();
        });
    }

    /**
     * Cài đặt của lượt làm bài: bản đang dùng lúc phát đề (admin sửa sau đó không làm đổi lượt đang làm).
     *
     * @return array<string, mixed>
     */
    public function settingsFor(TestSession $session): array
    {
        return $session->configVersion?->settings
            ? array_replace(TestConfig::DEFAULTS, $session->configVersion->settings)
            : TestConfig::current();
    }

    /**
     * Lưu câu trả lời của một trang (lưu dần để khách tải lại trang không mất bài). Chỉ nhận câu thuộc đề của lượt này, giá trị 1–5.
     *
     * @param  array<int, array{value: int, ms?: int|null}>  $answers  question_id => câu trả lời
     *
     * @throws InvalidArgumentException
     */
    public function saveAnswers(TestSession $session, array $answers): void
    {
        $allowed = $session->sessionQuestions()->pluck('question_id')->all();

        foreach ($answers as $questionId => $answer) {
            if (! in_array((int) $questionId, $allowed, true) || ! is_int($answer['value'] ?? null) || $answer['value'] < 1 || $answer['value'] > 5) {
                throw new InvalidArgumentException('Câu trả lời không hợp lệ.');
            }
        }

        DB::transaction(function () use ($session, $answers): void {
            foreach ($answers as $questionId => $answer) {
                $existing = $session->responses()->where('question_id', $questionId)->first();
                // Giữ thời gian của lần chọn đầu tiên: khách sửa lại câu không làm "nhanh" hơn thực tế.
                $session->responses()->updateOrCreate(['question_id' => $questionId], [
                    'value' => $answer['value'],
                    'ms' => $existing?->ms ?? ($answer['ms'] ?? null),
                ]);
            }
        });
    }

    /**
     * Đã trả lời đủ mọi câu thì chấm từ các câu đã lưu.
     */
    public function finishIfComplete(TestSession $session): ?TestSession
    {
        $responses = $session->responses()->get()->keyBy('question_id');

        if ($responses->count() < $session->sessionQuestions()->count()) {
            return null;
        }

        return $this->finish($session, $responses->map(fn ($r) => ['value' => (int) $r->value, 'ms' => $r->ms])->all());
    }

    /**
     * Mã câu của lần làm gần nhất trên cùng máy, để lần này ưu tiên câu khác.
     *
     * @return list<string>
     */
    private function previousCodes(?string $deviceHash): array
    {
        if ($deviceHash === null) {
            return [];
        }

        $previous = TestSession::query()->where('device_hash', $deviceHash)->latest('id')->first();

        return $previous
            ? Question::query()->whereIn('id', $previous->sessionQuestions()->pluck('question_id'))->pluck('code')->all()
            : [];
    }
}
