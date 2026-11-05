<?php

namespace Tests\Unit\Services;

use App\Enums\AnswerType;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Services\AnswerScorer;
use PHPUnit\Framework\TestCase;

class AnswerScorerTest extends TestCase
{
    private QuestionSet $set;

    protected function setUp(): void
    {
        parent::setUp();

        $this->set = new QuestionSet(['answer_type' => AnswerType::YesNo, 'scale' => AnswerType::YesNo->defaultScale()]);
    }

    private function question(array $attributes): Question
    {
        $question = new Question(['code' => 'B99', 'text' => 'Câu thử', 'is_required' => true, ...$attributes]);
        $question->setRelation('questionSet', $this->set);

        return $question;
    }

    /**
     * @return list<array{value: string, label: string, group: string|null, points: int}>
     */
    private function choices(): array
    {
        return [
            ['value' => '1', 'label' => 'Sửa xe', 'group' => 'R', 'points' => 1],
            ['value' => '2', 'label' => 'Lắp mạch điện', 'group' => 'R', 'points' => 2],
            ['value' => '3', 'label' => 'Vẽ tranh', 'group' => 'A', 'points' => 1],
            ['value' => '4', 'label' => 'Không có', 'group' => null, 'points' => 0],
        ];
    }

    public function test_yes_no_keeps_single_group_shape(): void
    {
        $entry = (new AnswerScorer)->entry($this->question(['answer_type' => AnswerType::YesNo, 'riasec' => 'I']), $this->set, '1');

        $this->assertSame('I', $entry['group']);
        $this->assertSame(1, $entry['value']);
        $this->assertSame(1, $entry['max']);
        $this->assertSame('Đúng', $entry['answer']);
        $this->assertSame([['group' => 'I', 'value' => 1, 'max' => 1]], $entry['scores']);
    }

    public function test_custom_scale_uses_highest_level_as_max(): void
    {
        $question = $this->question(['answer_type' => AnswerType::Scale, 'riasec' => 'S', 'options' => [
            ['value' => 0, 'label' => 'Không'], ['value' => 3, 'label' => 'Vừa'], ['value' => 6, 'label' => 'Rất'],
        ]]);

        $entry = (new AnswerScorer)->entry($question, $this->set, '3');

        $this->assertSame([['group' => 'S', 'value' => 3, 'max' => 6]], $entry['scores']);
        $this->assertSame('Vừa', $entry['answer']);
    }

    public function test_single_choice_scores_each_group_with_its_best_option_as_max(): void
    {
        $question = $this->question(['answer_type' => AnswerType::Single, 'options' => $this->choices()]);

        $entry = (new AnswerScorer)->entry($question, $this->set, '1');

        $this->assertNull($entry['group'], 'Câu cộng cho nhiều nhóm');
        $this->assertSame([['group' => 'R', 'value' => 1, 'max' => 2], ['group' => 'A', 'value' => 0, 'max' => 1]], $entry['scores']);
        $this->assertSame('Sửa xe', $entry['answer']);
    }

    public function test_multi_choice_sums_ticked_points_per_group(): void
    {
        $question = $this->question(['answer_type' => AnswerType::Multi, 'options' => $this->choices()]);

        $entry = (new AnswerScorer)->entry($question, $this->set, ['2', '3', '4']);

        $this->assertSame([['group' => 'R', 'value' => 2, 'max' => 3], ['group' => 'A', 'value' => 1, 'max' => 1]], $entry['scores']);
        $this->assertSame('Lắp mạch điện, Vẽ tranh, Không có', $entry['answer']);
        $this->assertSame(3, $entry['value']);
        $this->assertSame(4, $entry['max']);
    }

    public function test_skipped_optional_question_is_not_counted(): void
    {
        $scorer = new AnswerScorer;

        $this->assertNull($scorer->entry($this->question(['answer_type' => AnswerType::Multi, 'options' => $this->choices()]), $this->set, []));
        $this->assertNull($scorer->entry($this->question(['answer_type' => AnswerType::YesNo, 'riasec' => 'R']), $this->set, null));
    }

    public function test_engine_input_flattens_scores(): void
    {
        $entries = [
            ['scores' => [['group' => 'R', 'value' => 1, 'max' => 1]]],
            ['scores' => [['group' => 'R', 'value' => 0, 'max' => 2], ['group' => 'A', 'value' => 1, 'max' => 1]]],
        ];

        $this->assertCount(3, AnswerScorer::engineInput($entries));
        $this->assertSame([], AnswerScorer::engineInput([]));
    }
}
