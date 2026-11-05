<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Một lượt làm bài. `contact` (Phần A) được mã hóa trong database, chỉ giữ để gửi CRM.
 */
#[Fillable([
    'uuid', 'result_token', 'question_set_id', 'source', 'engine', 'engine_version', 'holland_code',
    'contact', 'answers', 'result', 'utm', 'crm_customer_code', 'crm_sync_status', 'appointment_at', 'completed_at',
])]
class Attempt extends Model
{
    public const SYNC_PENDING = 'pending';

    public const SYNC_SENT = 'sent';

    public const SYNC_FAILED = 'failed';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contact' => 'encrypted:array',
            'answers' => 'array',
            'result' => 'array',
            'utm' => 'array',
            'appointment_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<QuestionSet, $this>
     */
    public function questionSet(): BelongsTo
    {
        return $this->belongsTo(QuestionSet::class)->withTrashed();
    }

    /**
     * @return HasMany<OutboxMessage, $this>
     */
    public function outboxMessages(): HasMany
    {
        return $this->hasMany(OutboxMessage::class);
    }

    public function syncStatusLabel(): string
    {
        return match ($this->crm_sync_status) {
            self::SYNC_SENT => 'Đã gửi CRM',
            self::SYNC_FAILED => 'Gửi lỗi',
            default => 'Chờ gửi',
        };
    }
}
