<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một dòng trong "Lịch sử tư vấn" của lead.
 * - system: do hệ thống tự ghi (tiếp nhận, đổi trạng thái, phân công, cập nhật thông tin)
 * - còn lại: nhân viên nhập ở mục "Ghi chú và hoạt động"
 */
class LeadActivity extends Model
{
    /** key => [nhãn, icon path] */
    public const TYPES = [
        'note' => ['Ghi chú', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>'],
        'call' => ['Cuộc gọi', '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>'],
        'meeting' => ['Gặp mặt', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/>'],
        'message' => ['Zalo / Tin nhắn', '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
        'email' => ['Email', '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>'],
        'form' => ['Form liên hệ', '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>'],
        'system' => ['Hệ thống', '<circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/>'],
    ];

    /** Các loại nhân viên được chọn khi thêm ghi chú */
    public const MANUAL_TYPES = ['note', 'call', 'meeting', 'message', 'email'];

    protected $fillable = ['lead_id', 'user_id', 'type', 'content'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function typeIcon(): string
    {
        return self::TYPES[$this->type][1] ?? self::TYPES['note'][1];
    }
}
