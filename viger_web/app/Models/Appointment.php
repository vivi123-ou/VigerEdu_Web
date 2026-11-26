<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Làm gì:   Một lịch hẹn với khách (gọi điện, gặp mặt, online…), có kết quả sau buổi hẹn.
 * Ai gọi:   AppointmentController, trang hồ sơ khách, Dashboard.
 * Dữ liệu:  bảng appointments; ghi lịch sử vào lead_activities qua Lead::log().
 *
 * @property Carbon $scheduled_at
 */
class Appointment extends Model
{
    use HasFactory;

    /** key => [nhãn, icon path] */
    public const TYPES = [
        'call' => ['Gọi điện', '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>'],
        'office' => ['Gặp tại văn phòng', '<path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>'],
        'online' => ['Họp online', '<rect x="2" y="5" width="14" height="14" rx="2"/><path d="m22 8-6 4 6 4z"/>'],
        'other' => ['Khác', '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>'],
    ];

    /** key => [nhãn, class badge] */
    public const STATUSES = [
        'scheduled' => ['Đã hẹn', 'badge-blue'],
        'done' => ['Hoàn thành', 'badge-green'],
        'no_show' => ['Khách vắng mặt', 'badge-amber'],
        'cancelled' => ['Đã huỷ', 'badge-gray'],
    ];

    public const DURATIONS = [15 => '15 phút', 30 => '30 phút', 45 => '45 phút', 60 => '1 giờ', 90 => '1 giờ 30', 120 => '2 giờ'];

    protected $fillable = ['lead_id', 'user_id', 'created_by', 'scheduled_at', 'duration_minutes', 'type', 'location', 'note', 'status', 'result'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function endsAt(): Carbon
    {
        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function typeIcon(): string
    {
        return self::TYPES[$this->type][1] ?? self::TYPES['other'][1];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return self::STATUSES[$this->status][1] ?? 'badge-gray';
    }

    /** Đã qua giờ hẹn mà chưa cập nhật kết quả */
    public function isOverdue(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at->lt(now());
    }

    /** Mô tả ngắn dùng trong lịch sử tư vấn: "Gọi điện lúc 09:00 26/11/2026" */
    public function summary(): string
    {
        return $this->typeLabel().' lúc '.$this->scheduled_at->format('H:i d/m/Y');
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('status', 'scheduled')->where('scheduled_at', '>=', now());
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->where('status', 'scheduled')->where('scheduled_at', '<', now());
    }

    /**
     * Lịch "Đã hẹn" của cùng tư vấn viên bị chồng giờ với khoảng [start, start + minutes).
     * Lọc chồng giờ bằng PHP để chạy giống nhau trên MySQL và SQLite.
     */
    public static function conflictFor(?int $userId, Carbon $start, int $minutes, ?int $ignoreId = null): ?self
    {
        if (! $userId) {
            return null;
        }

        $end = $start->copy()->addMinutes($minutes);

        return self::query()
            ->where('user_id', $userId)
            ->where('status', 'scheduled')
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->whereBetween('scheduled_at', [$start->copy()->subHours(4), $end])
            ->with('lead')
            ->get()
            ->first(fn (self $a) => $a->scheduled_at->lt($end) && $a->endsAt()->gt($start));
    }
}
