<?php

namespace Tests\Feature\Assessment;

use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Services\QuestionPicker;
use App\Modules\Assessment\Support\TestConfig;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\QuestionBankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bốc đề theo tài liệu của Thầy: 6 câu mỗi nhóm, chỉ câu Đang dùng, đủ 4 chủ đề, trộn thứ tự, cùng hạt giống ra cùng đề,
 * câu ưu tiên cao hay được bốc hơn, hạn chế lặp câu lần trước, câu luôn có trong mọi đề, thiếu câu thì cảnh báo.
 */
class QuestionPickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CatalogSeeder::class, QuestionBankSeeder::class]);
    }

    public function test_each_draw_has_six_approved_questions_per_group_from_at_least_four_topics(): void
    {
        Question::whereIn('code', ['R001', 'R002', 'I001'])->update(['review_status' => Question::STATUS_SUSPENDED]);

        foreach (range(1, 20) as $seed) {
            $draw = (new QuestionPicker(TestConfig::DEFAULTS, $seed))->pick();
            $questions = $draw['questions'];

            $this->assertCount(36, $questions);
            $this->assertSame(36, $questions->pluck('id')->unique()->count(), 'Không lặp câu trong một đề');
            $this->assertSame([], $draw['warnings']);
            foreach (['R', 'I', 'A', 'S', 'E', 'C'] as $group) {
                $ofGroup = $questions->where('group', $group);
                $this->assertCount(6, $ofGroup, "Nhóm {$group}");
                $this->assertGreaterThanOrEqual(4, $ofGroup->pluck('question_facet_id')->unique()->count(), "Chủ đề nhóm {$group}");
            }
            $this->assertEmpty($questions->whereIn('code', ['R001', 'R002', 'I001']), 'Câu tạm ngưng không được bốc');
            $this->assertNotSame(['R', 'R', 'R', 'R', 'R', 'R'], $questions->take(6)->pluck('group')->all(), 'Thứ tự đã được trộn');
        }
    }

    public function test_same_seed_gives_the_same_test_and_different_seeds_differ(): void
    {
        $codes = fn (int $seed) => (new QuestionPicker(TestConfig::DEFAULTS, $seed))->pick()['questions']->pluck('code')->all();

        $this->assertSame($codes(42), $codes(42));
        $this->assertNotSame($codes(42), $codes(43));
    }

    public function test_high_priority_questions_are_drawn_more_often_than_low_priority(): void
    {
        $counts = ['A' => 0, 'C' => 0];
        foreach (range(1, 200) as $seed) {
            foreach ((new QuestionPicker(TestConfig::DEFAULTS, $seed))->pick()['questions'] as $question) {
                if (isset($counts[$question->priority_tier])) {
                    $counts[$question->priority_tier]++;
                }
            }
        }

        // Mỗi nhóm 10 câu mức Cao (trọng số 5), 15 câu mức Thấp (trọng số 1): mỗi câu Cao phải được bốc nhiều hơn hẳn.
        $this->assertGreaterThan(3 * ($counts['C'] / 15), $counts['A'] / 10);
    }

    public function test_avoids_questions_seen_last_time_when_enough_remain(): void
    {
        $first = (new QuestionPicker(TestConfig::DEFAULTS, 7))->pick()['questions']->pluck('code')->all();
        $second = (new QuestionPicker(TestConfig::DEFAULTS, 8))->pick($first)['questions']->pluck('code')->all();

        $this->assertSame([], array_values(array_intersect($first, $second)));
    }

    public function test_anchor_questions_are_always_included_when_enabled(): void
    {
        Question::whereIn('code', ['R050', 'S049'])->update(['is_anchor' => true]);
        $settings = ['use_anchors' => true] + TestConfig::DEFAULTS;

        foreach (range(1, 10) as $seed) {
            $codes = (new QuestionPicker($settings, $seed))->pick()['questions']->pluck('code');
            $this->assertContains('R050', $codes);
            $this->assertContains('S049', $codes);
        }
    }

    public function test_warns_when_a_group_has_too_few_questions_or_topics(): void
    {
        Question::where('group', 'E')->where('code', '>', 'E004')->update(['review_status' => Question::STATUS_SUSPENDED]);
        $draw = (new QuestionPicker(TestConfig::DEFAULTS, 1))->pick();
        $this->assertCount(4, $draw['questions']->where('group', 'E'));
        $this->assertStringContainsString('Nhóm E chỉ có 4 câu đang dùng', implode(' ', $draw['warnings']));

        Question::where('group', 'E')->update(['review_status' => Question::STATUS_APPROVED]);
        $twoFacets = Question::where('group', 'C')->pluck('question_facet_id')->unique()->take(2);
        Question::where('group', 'C')->whereNotIn('question_facet_id', $twoFacets)->update(['review_status' => Question::STATUS_SUSPENDED]);
        $draw = (new QuestionPicker(TestConfig::DEFAULTS, 1))->pick();
        $this->assertCount(6, $draw['questions']->where('group', 'C'), 'Vẫn ra đủ câu dù thiếu chủ đề');
        $this->assertStringContainsString('Nhóm C chỉ còn câu đang dùng của 2 chủ đề', implode(' ', $draw['warnings']), 'Thiếu chủ đề thì vẫn ra đề nhưng báo cho quản trị viên');
    }
}
