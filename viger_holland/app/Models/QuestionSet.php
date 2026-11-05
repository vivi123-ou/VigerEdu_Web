<?php

namespace App\Models;

use App\Enums\AnswerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bộ câu hỏi Phần B (Holland). Có thể có nhiều bộ, mỗi bộ một thang trả lời.
 */
#[Fillable(['code', 'name', 'description', 'answer_type', 'scale', 'per_page', 'shuffle_groups', 'is_default', 'is_active', 'sort_order'])]
class QuestionSet extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answer_type' => AnswerType::class,
            'scale' => 'array',
            'per_page' => 'integer',
            'shuffle_groups' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<QuestionSet>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
