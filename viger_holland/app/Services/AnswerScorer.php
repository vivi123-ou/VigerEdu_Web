<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionSet;

/**
 * Quy câu trả lời Holland của mọi kiểu câu (Đúng/Không, thang mức, chọn 1, chọn nhiều) về
 * "điểm đạt / điểm tối đa của từng nhóm". Engine chấm vẫn dùng đúng công thức p[g] = Σ điểm / Σ tối đa.
 *
 * - Thang mức: cộng cho nhóm của câu; tối đa = mức cao nhất (hoặc điểm tối đa admin đặt).
 * - Chọn 1: mỗi nhóm có trong các lựa chọn: đạt = điểm của lựa chọn đã chọn (nếu thuộc nhóm đó), tối đa = điểm cao nhất của nhóm.
 * - Chọn nhiều: mỗi nhóm: đạt = tổng điểm các ô đã tick thuộc nhóm, tối đa = tổng điểm mọi ô thuộc nhóm.
 * - Câu không bắt buộc mà khách bỏ trống: không tính (không cộng vào tối đa).
 */
final class AnswerScorer
{
    /**
     * Một dòng bài làm (gửi CRM trong quiz.answers), hoặc null nếu khách bỏ trống.
     *
     * @return array{code: string, type: string, text: string, answer: string, group: string|null, value: int|float, max: int|float, scores: list<array{group: string, value: int|float, max: int|float}>}|null
     */
    public function entry(Question $question, QuestionSet $set, mixed $answer): ?array
    {
        if ($answer === null || $answer === '' || $answer === []) {
            return null;
        }

        [$scores, $label] = $question->answer_type->isChoice()
            ? $this->choiceScores($question, (array) $answer)
            : $this->scaleScores($question, $set, $answer);

        $groups = array_column($scores, 'group');

        return [
            'code' => $question->code,
            'type' => $question->answer_type->value,
            'text' => $question->text,
            'answer' => $label,
            // Câu cộng cho đúng một nhóm: giữ group / value / max như trước; câu nhiều nhóm: group = null, xem scores.
            'group' => count(array_unique($groups)) === 1 ? $groups[0] : null,
            'value' => self::number(array_sum(array_column($scores, 'value'))),
            'max' => self::number(array_sum(array_column($scores, 'max'))),
            'scores' => $scores,
        ];
    }

    /**
     * Danh sách {group, value, max} đưa vào engine chấm.
     *
     * @param  list<array<string, mixed>>  $entries
     * @return list<array{group: string, value: int|float, max: int|float}>
     */
    public static function engineInput(array $entries): array
    {
        return array_values(array_merge([], ...array_column($entries, 'scores')));
    }

    /**
     * @return array{0: list<array{group: string, value: int|float, max: int|float}>, 1: string}
     */
    private function scaleScores(Question $question, QuestionSet $set, mixed $answer): array
    {
        $value = self::number((float) $answer);
        $label = collect($question->scale($set))->first(fn (array $option) => (string) $option['value'] === (string) $answer)['label'] ?? (string) $answer;

        if (blank($question->riasec)) {
            return [[], $label];
        }

        return [[['group' => $question->riasec, 'value' => $value, 'max' => self::number($question->maxPoints($set))]], $label];
    }

    /**
     * @param  list<string>  $selected
     * @return array{0: list<array{group: string, value: int|float, max: int|float}>, 1: string}
     */
    private function choiceScores(Question $question, array $selected): array
    {
        $selected = array_map('strval', $selected);
        $choices = collect($question->choices());
        $isMulti = $question->answer_type->value === 'multi';

        $labels = $choices->filter(fn (array $choice) => in_array((string) $choice['value'], $selected, true))->pluck('label')->all();

        $scores = $choices
            ->filter(fn (array $choice) => filled($choice['group'] ?? null) && (float) ($choice['points'] ?? 0) > 0)
            ->groupBy('group')
            ->map(function ($groupChoices, string $group) use ($selected, $isMulti): array {
                $points = $groupChoices->map(fn (array $choice) => (float) $choice['points']);
                $earned = $groupChoices->filter(fn (array $choice) => in_array((string) $choice['value'], $selected, true))
                    ->sum(fn (array $choice) => (float) $choice['points']);

                return [
                    'group' => $group,
                    'value' => self::number($isMulti ? $earned : min($earned, $points->max())),
                    'max' => self::number($isMulti ? $points->sum() : $points->max()),
                ];
            })
            ->values()
            ->all();

        return [$scores, implode(', ', $labels)];
    }

    private static function number(float|int $value): int|float
    {
        $number = round((float) $value, 4);

        return floor($number) === $number ? (int) $number : $number;
    }
}
