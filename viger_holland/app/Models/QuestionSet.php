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

    /**
     * Thang trả lời của bộ: nhãn admin đặt, không có thì thang mặc định của kiểu trả lời.
     *
     * @return list<array{value: int|float, label: string}>
     */
    public function scaleOptions(): array
    {
        return ! empty($this->scale) ? $this->scale : $this->answer_type->defaultScale();
    }

    /**
     * Link làm bài với bộ câu này (gửi cho khách / gắn quảng cáo).
     */
    public function testUrl(): string
    {
        return $this->is_default ? route('test.show') : route('test.show', ['bo' => $this->code]);
    }

    /**
     * Đặt bộ này làm bộ mặc định (chỉ một bộ mặc định).
     */
    public function makeDefault(): void
    {
        static::query()->whereKeyNot($this->getKey())->update(['is_default' => false]);
        $this->forceFill(['is_default' => true, 'is_active' => true])->save();
    }

    /**
     * Phiên bản bộ câu gửi CRM (ngày sửa gần nhất).
     */
    public function version(): string
    {
        return ($this->updated_at ?? now())->format('Y-m-d');
    }
}
