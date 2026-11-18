<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Models\Question;
use Illuminate\Support\Collection;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Làm gì:   Bốc đề cho một lượt làm bài theo tài liệu của Thầy:
 *           mỗi nhóm R I A S E C lấy câu "luôn có trong mọi đề" (nếu bật), rồi bốc thêm cho đủ "Mỗi nhóm bốc mấy câu"
 *           trong số câu "Đang dùng", ngẫu nhiên có trọng số, không lặp (Efraimidis–Spirakis: khóa = −ln(u) / trọng số, lấy khóa nhỏ nhất);
 *           phải đủ "ít nhất mấy chủ đề khác nhau"; thử tối đa "Bốc thử tối đa mấy lần", không được thì nới điều kiện và ghi cảnh báo;
 *           hạn chế gặp lại câu của lần làm trước trên cùng máy; cuối cùng trộn thứ tự cả đề.
 *           Trọng số chỉ để bốc đề, không dùng tính điểm.
 * Ai gọi:   AssessmentService::start().
 * Dữ liệu:  bảng questions (chỉ câu Đang dùng, chưa vào thùng rác); cài đặt TestConfig::current(). Không có dữ liệu khách.
 */
final class QuestionPicker
{
    private Randomizer $random;

    /**
     * @param  array<string, mixed>  $settings  TestConfig::current()
     * @param  int  $seed  hạt giống ngẫu nhiên, lưu lại cùng lượt làm bài để có thể dựng lại đúng đề
     */
    public function __construct(private readonly array $settings, private readonly int $seed)
    {
        $this->random = new Randomizer(new Mt19937($seed));
    }

    /**
     * @param  list<string>  $previousCodes  mã câu của lần làm trước trên cùng máy (để hạn chế lặp)
     * @return array{questions: Collection<int, Question>, warnings: list<string>, seed: int}
     */
    public function pick(array $previousCodes = []): array
    {
        $perGroup = (int) $this->settings['items_per_group'];
        $pool = Question::query()->approved()->get()->groupBy('group');
        $picked = collect();
        $warnings = [];

        foreach (str_split(ScoringEngine::ORDER) as $group) {
            [$questions, $groupWarnings] = $this->pickGroup($group, $pool->get($group, collect()), $perGroup, $previousCodes);
            $picked = $picked->merge($questions);
            $warnings = [...$warnings, ...$groupWarnings];
        }

        return ['questions' => $this->shuffle($picked), 'warnings' => $warnings, 'seed' => $this->seed];
    }

    /**
     * @param  Collection<int, Question>  $pool
     * @param  list<string>  $previousCodes
     * @return array{0: Collection<int, Question>, 1: list<string>}
     */
    private function pickGroup(string $group, Collection $pool, int $perGroup, array $previousCodes): array
    {
        $warnings = [];

        if ($pool->count() < $perGroup) {
            $warnings[] = "Nhóm {$group} chỉ có {$pool->count()} câu đang dùng, cần {$perGroup}: đề nhóm này thiếu câu.";

            return [$pool->values(), $warnings];
        }

        $anchors = $this->settings['use_anchors'] ? $pool->where('is_anchor', true)->take($perGroup)->values() : collect();
        $rest = $pool->reject(fn (Question $q) => $anchors->contains('id', $q->id))->values();

        if ($this->settings['avoid_repeat'] && $previousCodes !== []) {
            $fresh = $rest->reject(fn (Question $q) => in_array($q->code, $previousCodes, true))->values();
            if ($fresh->count() >= $perGroup - $anchors->count()) {
                $rest = $fresh;
            } else {
                $warnings[] = "Nhóm {$group}: không đủ câu mới nên có thể gặp lại câu của lần trước.";
            }
        }

        $need = $perGroup - $anchors->count();
        $availableFacets = $pool->pluck('question_facet_id')->unique()->count();
        $minFacets = min((int) $this->settings['min_distinct_facets'], $availableFacets);
        if ($availableFacets < (int) $this->settings['min_distinct_facets']) {
            $warnings[] = "Nhóm {$group} chỉ còn câu đang dùng của {$availableFacets} chủ đề, cần ít nhất {$this->settings['min_distinct_facets']}: đề nhóm này có thể lệch về một dạng hoạt động.";
        }
        $attempts = max(1, (int) $this->settings['max_generation_attempts']);

        for ($try = 1; $try <= $attempts; $try++) {
            $chosen = $anchors->merge($this->weightedSample($rest, $need));
            if ($chosen->pluck('question_facet_id')->unique()->count() >= $minFacets) {
                return [$chosen->values(), $warnings];
            }
        }

        $warnings[] = "Nhóm {$group}: sau {$attempts} lần bốc vẫn chưa đủ {$minFacets} chủ đề khác nhau, đã nới điều kiện.";

        return [$anchors->merge($this->weightedSample($rest, $need))->values(), $warnings];
    }

    /**
     * Bốc k câu không lặp theo trọng số (Efraimidis–Spirakis): mỗi câu nhận khóa −ln(u) / trọng số, lấy k khóa nhỏ nhất.
     * Câu trọng số 0 chỉ được bốc khi không còn câu nào khác.
     *
     * @param  Collection<int, Question>  $questions
     * @return Collection<int, Question>
     */
    private function weightedSample(Collection $questions, int $k): Collection
    {
        return $questions
            ->map(function (Question $question): array {
                $u = $this->random->nextFloat() ?: PHP_FLOAT_MIN;
                $weight = (float) $question->selection_weight;

                return ['question' => $question, 'key' => $weight > 0 ? -log($u) / $weight : INF];
            })
            ->sortBy('key')
            ->take($k)
            ->pluck('question')
            ->values();
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return Collection<int, Question>
     */
    private function shuffle(Collection $questions): Collection
    {
        return collect($this->random->shuffleArray($questions->values()->all()));
    }
}
