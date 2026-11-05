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
}
