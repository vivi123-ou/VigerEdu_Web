<?php

namespace App\Support;

use App\Enums\AnswerType;
use App\Models\Question;

/**
 * Dòng mô tả ngắn trên thẻ câu hỏi: "Chọn nhiều · cộng điểm cho Kỹ thuật, Xã hội · không bắt buộc · đang ẩn".
 */
final class QuestionSummary
{
    public static function holland(Question $question): string
    {
        $groups = array_map(fn (string $letter) => setting("group_name_{$letter}", $letter), $question->scoredGroups());

        return implode(' · ', array_filter([
            $question->answer_type->label(),
            $groups ? 'cộng điểm cho '.implode(', ', $groups) : 'chưa chọn nhóm',
            $question->is_required ? null : 'không bắt buộc',
            $question->is_active ? null : 'đang ẩn',
        ]));
    }

    public static function info(Question $question): string
    {
        if ($question->answer_type === AnswerType::Consent) {
            return 'Ô đồng ý · luôn ở cuối form';
        }

        return implode(' · ', array_filter([
            $question->answer_type->label(),
            $question->is_required ? 'bắt buộc' : null,
            empty($question->show_if['question']) ? null : 'chỉ hỏi khi có điều kiện',
            $question->is_active ? null : 'đang ẩn',
        ]));
    }
}
