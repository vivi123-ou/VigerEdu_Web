<?php

namespace App\Modules\Assessment\Models;

use App\Modules\Assessment\Policies\QuestionPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Làm gì:   Một câu trong thư viện 300 câu; selection_weight chỉ quyết định khả năng được rút vào đề, không dùng tính điểm.
 * Ai gọi:   QuestionPicker (H2) rút đề; trang Thư viện câu hỏi (Filament); QuestionBankSeeder.
 * Dữ liệu:  bảng questions (xóa mềm), question_weight_audits.
 */
#[UsePolicy(QuestionPolicy::class)]
#[Fillable(['code', 'group', 'question_facet_id', 'text', 'priority_tier', 'selection_weight', 'is_anchor', 'review_status', 'validation_status', 'language', 'target_group', 'item_version', 'review_note', 'source_reference', 'last_reviewed_at'])]
class Question extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SUSPENDED = 'suspended';

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Nháp',
        self::STATUS_APPROVED => 'Đã duyệt',
        self::STATUS_SUSPENDED => 'Tạm ngưng',
    ];

    /** @var array<string, string> */
    public const TIER_LABELS = ['A' => 'Lõi', 'B' => 'Chuẩn', 'C' => 'Mở rộng'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selection_weight' => 'float',
            'is_anchor' => 'boolean',
            'last_reviewed_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<QuestionFacet, $this>
     */
    public function facet(): BelongsTo
    {
        return $this->belongsTo(QuestionFacet::class, 'question_facet_id');
    }

    /**
     * @return HasMany<QuestionWeightAudit, $this>
     */
    public function weightAudits(): HasMany
    {
        return $this->hasMany(QuestionWeightAudit::class);
    }

    /**
     * @param  Builder<Question>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('review_status', self::STATUS_APPROVED);
    }
}
