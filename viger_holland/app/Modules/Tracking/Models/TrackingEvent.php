<?php

namespace App\Modules\Tracking\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Một hành vi của khách trong lượt làm bài (bắt đầu, xong trang, xong bài, gửi form, mở báo cáo, bấm Gọi / Zalo).
 *           Gắn với lượt làm bài, không gắn trực tiếp với người; gửi sang CRM kèm gói lead hoặc bằng events/1.
 * Ai gọi:   Tracker.
 * Dữ liệu:  bảng tracking_events (không có dữ liệu cá nhân trong meta).
 */
#[Fillable(['test_session_id', 'type', 'meta', 'occurred_at', 'sent_at'])]
class TrackingEvent extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['meta' => 'array', 'occurred_at' => 'datetime', 'sent_at' => 'datetime'];
    }
}
