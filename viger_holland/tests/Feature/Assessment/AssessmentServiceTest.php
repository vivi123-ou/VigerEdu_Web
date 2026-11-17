<?php

namespace Tests\Feature\Assessment;

use App\Modules\Assessment\Models\Major;
use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Services\AssessmentService;
use App\Modules\Assessment\Support\TestConfig;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\QuestionBankSeeder;
use Database\Seeders\TestConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Một lượt làm bài từ đầu đến cuối: phát đề (lưu nội dung câu lúc phát), chấm, gợi ý ngành, lưu kết quả;
 * phần khách thấy chỉ có mã 2 chữ (hoặc 3 chữ khi đủ điều kiện) và nhãn chữ; cài đặt / câu hỏi sửa sau không làm đổi kết quả cũ.
 */
class AssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    /** Điểm từng câu theo nhóm để ra đúng hồ sơ HS01 của Thầy (I 4,67 · S 4,33 · A 4,17 · R 3,33 · C 3,17 · E 2,33). */
    private const HS01 = [
        'I' => [5, 5, 5, 5, 4, 4], 'S' => [5, 5, 4, 4, 4, 4], 'A' => [5, 4, 4, 4, 4, 4],
        'R' => [4, 4, 3, 3, 3, 3], 'C' => [4, 3, 3, 3, 3, 3], 'E' => [3, 3, 2, 2, 2, 2],
    ];

    private AssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CatalogSeeder::class, QuestionBankSeeder::class, TestConfigSeeder::class]);
        $this->service = app(AssessmentService::class);
    }

    public function test_start_saves_36_questions_with_snapshot_seed_and_settings_version(): void
    {
        $session = $this->service->start('may-1', ['utm_source' => 'facebook']);

        $this->assertSame('in_progress', $session->status);
        $this->assertTrue(Str::isUuid($session->result_token));
        $this->assertSame(TestConfig::currentVersion()->id, $session->test_config_version_id);
        $this->assertNotEmpty($session->generation_seed);
        $this->assertSame(['utm_source' => 'facebook'], $session->utm);

        $items = $session->sessionQuestions()->orderBy('display_order')->get();
        $this->assertCount(36, $items);
        $this->assertSame(range(1, 36), $items->pluck('display_order')->all());
        $first = $items->first();
        $this->assertSame(Question::find($first->question_id)->text, $first->text_snapshot);
    }

    public function test_finish_scores_saves_result_and_shows_customer_only_two_letters_and_labels(): void
    {
        $session = $this->service->start();
        $done = $this->service->finish($session, $this->answersFor($session, self::HS01));

        $this->assertSame('answered', $done->status);
        $this->assertSame('IS', $done->code2);
        $this->assertSame('ISA', $done->code3);
        $this->assertSame('A', $done->profile_case);
        $this->assertFalse($done->needs_counseling);
        $this->assertSame('Tin cậy', $done->reliability);
        $this->assertEqualsWithDelta(4.6667, $done->scores['I'], 0.0001);
        $this->assertCount(36, $done->responses);

        $customer = $done->result['customer'];
        $this->assertSame('IS', $customer['code'], 'Nhóm thứ 3 (4,17) chưa qua 4,2 nên khách chỉ thấy 2 chữ');
        $this->assertCount(5, $customer['majors']);
        $this->assertSame('Tâm lý học', $customer['majors'][0]['name_vi']);
        $this->assertArrayNotHasKey('c', $customer['majors'][0], 'Khách không thấy con số mức khớp');
        $this->assertGreaterThan(5, count($done->result['majors']), 'Tư vấn viên thấy toàn bộ ngành');
    }

    public function test_wrong_or_incomplete_answers_are_rejected(): void
    {
        $session = $this->service->start();
        $answers = $this->answersFor($session, self::HS01);

        $missing = $answers;
        array_pop($missing);
        $this->assertRejected($session, $missing);

        $outOfRange = $answers;
        $outOfRange[array_key_first($outOfRange)]['value'] = 6;
        $this->assertRejected($session, $outOfRange);

        $foreign = $answers;
        unset($foreign[array_key_first($foreign)]);
        $foreign[Question::whereNotIn('id', array_keys($answers))->value('id')] = ['value' => 3];
        $this->assertRejected($session, $foreign);

        $this->assertSame('in_progress', $session->fresh()->status);
    }

    public function test_result_uses_the_settings_and_question_text_from_when_the_test_started(): void
    {
        $session = $this->service->start();
        $firstItem = $session->sessionQuestions()->first();

        TestConfig::save(['third_letter_threshold' => 4.1] + TestConfig::current(), null, 'Hạ ngưỡng chữ thứ 3');
        Question::whereKey($firstItem->question_id)->update(['text' => 'Câu đã sửa sau khi phát đề']);

        $done = $this->service->finish($session, $this->answersFor($session, self::HS01));

        $this->assertSame('IS', $done->result['customer']['code'], 'Vẫn dùng ngưỡng 4,2 của lúc bắt đầu');
        $this->assertNotSame('Câu đã sửa sau khi phát đề', $firstItem->fresh()->text_snapshot);

        $later = $this->service->finish($this->service->start(), $this->answersFor(TestSession::latest('id')->first(), self::HS01));
        $this->assertSame('ISA', $later->result['customer']['code'], 'Lượt mới dùng ngưỡng mới 4,1');
    }

    public function test_only_verified_majors_switch_controls_what_the_customer_sees(): void
    {
        TestConfig::save(['only_verified_majors' => true] + TestConfig::current(), null, 'Bật chỉ ngành đã kiểm tra');
        Major::where('code', 'MAJ021')->update(['is_verified' => true, 'source_url' => 'https://www.hochschulkompass.de/']);

        $done = $this->service->finish($session = $this->service->start(), $this->answersFor($session, self::HS01));

        $this->assertSame(['Tâm lý học'], array_column($done->result['customer']['majors'], 'name_vi'));
        $this->assertFalse(collect($done->result['majors'])->firstWhere('id', 'MAJ033')['verified'], 'Tư vấn viên vẫn thấy ngành chưa kiểm tra, có đánh dấu');
    }

    public function test_pair_majors_first_switch(): void
    {
        $on = $this->service->finish($session = $this->service->start(), $this->answersFor($session, self::HS01));
        $this->assertSame('Khoa học sức khỏe', $on->result['majors'][1]['name_vi'], 'Bật: ngành nền của cặp IS đứng thứ 2 dù mức khớp chỉ 5');

        TestConfig::save(['pair_majors_first' => false] + TestConfig::current(), null, 'Tắt ưu tiên ngành nền');
        $off = $this->service->finish($session = $this->service->start(), $this->answersFor($session, self::HS01));
        $names = array_column($off->result['majors'], 'name_vi');
        $this->assertNotContains('Khoa học sức khỏe', $names, 'Tắt: ngành có mức khớp 5 không đủ nhãn nên không được gợi ý');
        $this->assertSame('Khoa học môi trường', $names[0], 'Tắt: xếp chung theo mức khớp, 16 đứng đầu');
    }

    public function test_same_device_gets_different_questions_next_time(): void
    {
        $first = $this->service->start('may-cua-khach')->sessionQuestions()->pluck('question_id')->all();
        $second = $this->service->start('may-cua-khach')->sessionQuestions()->pluck('question_id')->all();
        $otherDevice = $this->service->start('may-khac')->sessionQuestions()->pluck('question_id')->all();

        $this->assertSame([], array_values(array_intersect($first, $second)));
        $this->assertCount(36, $otherDevice);
    }

    /**
     * @param  array<string, list<int>>  $valuesByGroup
     * @return array<int, array{value: int, ms: int}>
     */
    private function answersFor(TestSession $session, array $valuesByGroup): array
    {
        $answers = [];
        $used = array_fill_keys(array_keys($valuesByGroup), 0);

        foreach ($session->sessionQuestions()->orderBy('display_order')->get() as $item) {
            $answers[$item->question_id] = ['value' => $valuesByGroup[$item->group][$used[$item->group]++], 'ms' => 4000];
        }

        return $answers;
    }

    /**
     * @param  array<int, array{value: int}>  $answers
     */
    private function assertRejected(TestSession $session, array $answers): void
    {
        try {
            $this->service->finish($session, $answers);
            $this->fail('Phải từ chối câu trả lời sai');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }
    }
}
