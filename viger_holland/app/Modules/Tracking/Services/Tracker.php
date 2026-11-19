<?php

namespace App\Modules\Tracking\Services;

use App\Modules\Assessment\Models\TestSession;
use App\Modules\CrmSync\Services\CrmSync;
use App\Modules\Tracking\Models\TrackingEvent;
use Throwable;

/**
 * Làm gì:   Ghi hành vi của khách trong lượt làm bài theo bảng loại sự kiện của hợp đồng (mục 2.2).
 *           Trước khi có mã khách CRM: hành vi chỉ gắn với lượt làm bài, đi kèm gói lead. Sau khi có mã: gửi ngay bằng events/1.
 *           Ghi hành vi lỗi thì bỏ qua, không làm hỏng trang khách.
 * Ai gọi:   QuizController, ReportController, TrackController (nút Gọi / Zalo trên báo cáo).
 * Dữ liệu:  tracking_events. meta không chứa dữ liệu cá nhân.
 */
class Tracker
{
    public const TYPES = [
        'quiz_started', 'quiz_page_completed', 'quiz_finished',
        'form_submitted', 'report_unlocked', 'report_viewed',
        'cta_call_clicked', 'cta_zalo_clicked',
    ];

    public function __construct(private readonly CrmSync $sync) {}

    /**
     * @param  array<string, scalar>  $meta
     */
    public function record(TestSession $session, string $type, array $meta = []): void
    {
        if (! in_array($type, self::TYPES, true)) {
            return;
        }

        try {
            TrackingEvent::query()->create([
                'test_session_id' => $session->id, 'type' => $type, 'meta' => $meta ?: null, 'occurred_at' => now(),
            ]);

            if (filled($session->respondent?->crm_customer_code)) {
                $this->sync->queueEvents($session);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
