<?php

namespace App\Models;

use App\Enums\AnswerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Câu hỏi kiểu Google Form. Phần A: form thông tin khách (lead_field / extra_field_key cho biết gửi sang CRM vào trường nào).
 * Phần B: câu Holland, thuộc một bộ câu. Câu thang mức cộng điểm cho nhóm `riasec`; câu chọn 1 / chọn nhiều
 * cộng điểm theo từng lựa chọn (options[].group, options[].points).
 *
 * config: min, max (câu số), min_select, max_select (chọn nhiều), allow_other ("Khác…"), private (không hiện ở trang Lượt làm bài).
 */
#[Fillable([
    'code', 'part', 'question_set_id', 'text', 'help_text', 'placeholder', 'form', 'answer_type', 'options', 'display', 'config',
    'is_required', 'show_if', 'lead_field', 'extra_field_key', 'riasec', 'max_score', 'sort_order', 'is_active', 'note',
])]
class Question extends Model
{
    use SoftDeletes;

    /** Sửa câu thì "phiên bản bộ câu" (ngày sửa) gửi CRM cũng đổi theo. */
    protected $touches = ['questionSet'];

    public const PART_INFO = 'A';

    public const PART_HOLLAND = 'B';

    /** Giá trị của lựa chọn "Khác (ghi rõ)…". */
    public const OTHER = '__other';

    public const DISPLAY_BUTTONS = 'buttons';

    public const DISPLAY_STARS = 'stars';

    public const DISPLAY_DROPDOWN = 'dropdown';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answer_type' => AnswerType::class,
            'options' => 'array',
            'config' => 'array',
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
     * Thang trả lời của câu kiểu thang mức: thang riêng của câu; không có thì theo bộ câu (nếu cùng kiểu với bộ),
     * không nữa thì thang mặc định của kiểu.
     *
     * @return list<array{value: int|float, label: string}>
     */
    public function scale(?QuestionSet $set = null): array
    {
        if (! empty($this->options)) {
            return $this->options;
        }

        $set ??= $this->questionSet;

        if ($set !== null && $set->answer_type === $this->answer_type) {
            return $set->scaleOptions();
        }

        return $this->answer_type->defaultScale();
    }

    /**
     * Lựa chọn của câu chọn 1 / chọn nhiều: [{value, label, group?, points?}].
     *
     * @return list<array{value: string, label: string, group?: string|null, points?: int|float}>
     */
    public function choices(): array
    {
        return array_values($this->options ?? []);
    }

    /**
     * Cách hiện: ô số / nút (buttons), ngôi sao (stars), danh sách sổ xuống (dropdown).
     * Câu không đặt riêng thì theo bộ câu (câu thang mức), không nữa thì nút.
     */
    public function displayStyle(?QuestionSet $set = null): string
    {
        if (filled($this->display)) {
            return $this->display;
        }

        $set ??= $this->questionSet;

        if ($this->answer_type->isScale() && filled($set?->display)) {
            return $set->display;
        }

        return $this->answer_type === AnswerType::Rating ? self::DISPLAY_STARS : self::DISPLAY_BUTTONS;
    }

    public function configValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function allowsOther(): bool
    {
        return $this->answer_type->isChoice() && (bool) $this->configValue('allow_other', false);
    }

    /**
     * Thông tin riêng tư: không hiện ở trang Lượt làm bài (xem ở CRM).
     * SĐT, email, ô đồng ý luôn riêng tư; câu khác do admin tick "Thông tin riêng tư".
     */
    public function isPrivate(): bool
    {
        return self::isAlwaysPrivate($this->answer_type) || (bool) $this->configValue('private', false);
    }

    public static function isAlwaysPrivate(?AnswerType $type): bool
    {
        return in_array($type, [AnswerType::Phone, AnswerType::Email, AnswerType::Consent], true);
    }

    /**
     * Các nhóm Holland mà câu này cộng điểm.
     *
     * @return list<string>
     */
    public function scoredGroups(): array
    {
        if (! $this->answer_type->isChoice()) {
            return filled($this->riasec) ? [$this->riasec] : [];
        }

        return collect($this->choices())
            ->filter(fn (array $choice) => filled($choice['group'] ?? null) && (float) ($choice['points'] ?? 0) > 0)
            ->pluck('group')->unique()->values()->all();
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
     * Mã câu tiếp theo của một phần (A23, B55…), tính cả câu trong thùng rác để không trùng mã cũ.
     */
    public static function nextCode(string $part, int $offset = 0): string
    {
        $max = static::withTrashed()
            ->where('code', 'like', $part.'%')
            ->pluck('code')
            ->map(fn (string $code) => preg_match('/^'.$part.'(\d+)$/', $code, $match) ? (int) $match[1] : 0)
            ->max() ?? 0;

        return $part.str_pad((string) ($max + 1 + $offset), 2, '0', STR_PAD_LEFT);
    }

    public function isHolland(): bool
    {
        return $this->part === self::PART_HOLLAND;
    }

    /**
     * Tên ngắn để ghi nhật ký: "B12 – Người thích…".
     */
    public function shortLabel(): string
    {
        return $this->code.' – '.mb_strimwidth($this->text, 0, 60, '…');
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
