<?php

namespace App\Models;

use App\Enums\AnswerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Câu hỏi. Phần A: form thông tin khách (lead_field / extra_field_key cho biết gửi sang CRM vào trường nào).
 * Phần B: câu Holland, thuộc một bộ câu, có nhóm RIASEC và điểm tối đa.
 */
#[Fillable([
    'code', 'part', 'question_set_id', 'text', 'help_text', 'placeholder', 'form', 'answer_type', 'options',
    'is_required', 'show_if', 'lead_field', 'extra_field_key', 'riasec', 'max_score', 'sort_order', 'is_active', 'note',
])]
class Question extends Model
{
    use SoftDeletes;

    public const PART_INFO = 'A';

    public const PART_HOLLAND = 'B';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answer_type' => AnswerType::class,
            'options' => 'array',
            'show_if' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'max_score' => 'float',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<QuestionSet, $this>
     */
    public function questionSet(): BelongsTo
    {
        return $this->belongsTo(QuestionSet::class);
    }

    /**
     * @param  Builder<Question>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Question>  $query
     */
    public function scopePart(Builder $query, string $part): void
    {
        $query->where('part', $part);
    }

    /**
     * @param  Builder<Question>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('code');
    }

    /**
     * Câu Phần A có hiện với các câu trả lời hiện tại không (điều kiện show_if).
     *
     * @param  array<string, mixed>  $info  [mã câu => câu trả lời]
     */
    public function isVisibleFor(array $info): bool
    {
        $condition = $this->show_if;

        if (empty($condition['question'])) {
            return true;
        }

        $value = $info[$condition['question']] ?? null;

        return in_array((string) $value, array_map('strval', $condition['in'] ?? []), true);
    }

    /**
     * Thang trả lời của câu Phần B: lựa chọn riêng của câu, nếu không thì theo bộ câu.
     *
     * @return list<array{value: int|float, label: string}>
     */
    public function scale(?QuestionSet $set = null): array
    {
        if (! empty($this->options)) {
            return $this->options;
        }

        $set ??= $this->questionSet;

        return $set?->scaleOptions() ?? $this->answer_type->defaultScale();
    }

    /**
     * Điểm tối đa của câu (dùng chấm %): max_score nếu admin đặt, không thì mức cao nhất của thang.
     */
    public function maxPoints(?QuestionSet $set = null): float
    {
        if ($this->max_score !== null && $this->max_score > 0) {
            return (float) $this->max_score;
        }

        return (float) max(array_column($this->scale($set), 'value') ?: [1]);
    }

    /**
     * Khóa gửi CRM: trường chuẩn lead/1, hoặc extra_fields.{mã}.
     */
    public function crmKey(): ?string
    {
        if (filled($this->lead_field)) {
            return $this->lead_field;
        }

        return filled($this->extra_field_key) ? 'extra_fields.'.$this->extra_field_key : null;
    }
}
