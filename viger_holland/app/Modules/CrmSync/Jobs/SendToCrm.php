<?php

namespace App\Modules\CrmSync\Jobs;

use App\Modules\CrmSync\Models\CrmOutbox;
use App\Modules\CrmSync\Services\CrmConfig;
use App\Modules\CrmSync\Services\CrmSync;
use App\Modules\CrmSync\Services\LeadPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Làm gì:   Gửi MỘT gói trong crm_outbox sang CRM (POST /api/v1/leads hoặc /api/v1/events, header X-Api-Token).
 *           2xx → "Đã gửi" (lead: lưu mã khách CRM, đánh dấu hành vi đã gửi, gửi tiếp hành vi phát sinh sau đó);
 *           422 → "Lỗi" (chỉ ghi tên trường bị từ chối); 401 / 403 / 429 / 5xx / mất mạng → gửi lại theo lịch
 *           (mặc định 1, 5, 30 phút, 2 giờ, 6 giờ), hết lượt → "Lỗi". Kết nối đang tắt → "Đang tắt kết nối".
 *           Log chỉ có event_id và mã HTTP, không có họ tên, email, số điện thoại.
 * Ai gọi:   CrmSync (khi xếp gói), lệnh crm:retry (theo lịch), nút "Gửi lại" trên trang admin.
 * Dữ liệu:  crm_outbox, respondents.crm_customer_code, tracking_events.sent_at.
 */
class SendToCrm implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $outboxId) {}

    public function handle(CrmConfig $config, CrmSync $sync): void
    {
        $outbox = CrmOutbox::query()->find($this->outboxId);
        if ($outbox === null || $outbox->status !== CrmOutbox::PENDING) {
            return;
        }
        if (! $config->ready()) {
            $outbox->update(['status' => CrmOutbox::DISABLED, 'next_try_at' => null]);

            return;
        }

        $path = $outbox->schema === 'events/1' ? '/api/v1/events' : '/api/v1/leads';

        try {
            $response = Http::withHeaders(['X-Api-Token' => $config->token()])
                ->acceptJson()->timeout(10)
                ->post($config->url().$path, LeadPayload::forWire($outbox->payload));
        } catch (Throwable $e) {
            $this->retryLater($outbox, $config, null, 'Không kết nối được CRM ('.class_basename($e).').');

            return;
        }

        if ($response->successful()) {
            $this->sent($outbox, $response, $sync);

            return;
        }

        if ($response->status() === 422) {
            $fields = array_keys((array) $response->json('errors', []));
            $outbox->update([
                'status' => CrmOutbox::FAILED, 'tries' => $outbox->tries + 1, 'last_http_status' => 422, 'next_try_at' => null,
                'last_error' => 'CRM từ chối dữ liệu'.($fields ? ' ở các trường: '.implode(', ', array_slice($fields, 0, 10)) : '').'.',
            ]);
            Log::warning('CRM từ chối gói', ['event_id' => $outbox->event_id, 'http' => 422]);

            return;
        }

        $this->retryLater($outbox, $config, $response->status(), match (true) {
            in_array($response->status(), [401, 403], true) => 'CRM báo sai token hoặc token không có quyền.',
            $response->status() === 429 => 'CRM báo gửi quá nhiều, sẽ gửi lại sau.',
            default => 'CRM đang lỗi (mã '.$response->status().'), sẽ gửi lại sau.',
        });
    }

    private function sent(CrmOutbox $outbox, Response $response, CrmSync $sync): void
    {
        $outbox->update([
            'status' => CrmOutbox::SENT, 'sent_at' => now(), 'tries' => $outbox->tries + 1,
            'last_http_status' => $response->status(), 'last_error' => null, 'next_try_at' => null,
        ]);
        Log::info('Đã gửi gói sang CRM', ['event_id' => $outbox->event_id, 'http' => $response->status()]);

        if ($outbox->schema !== 'lead/2') {
            return;
        }

        $session = $outbox->testSession()->with('respondent')->first();
        $code = $response->json('customer_code');
        if ($session?->respondent && is_string($code) && $code !== '') {
            $session->respondent->update(['crm_customer_code' => mb_substr($code, 0, 40)]);
        }

        // Hành vi phát sinh sau khi đóng gói lead (ví dụ khách tải lại báo cáo) gửi tiếp bằng events/1.
        if ($session !== null) {
            $sync->queueEvents($session->fresh('respondent'));
        }
    }

    private function retryLater(CrmOutbox $outbox, CrmConfig $config, ?int $status, string $error): void
    {
        $tries = $outbox->tries + 1;
        $schedule = $config->retryMinutes();
        $giveUp = $tries > count($schedule);

        $outbox->update([
            'status' => $giveUp ? CrmOutbox::FAILED : CrmOutbox::PENDING,
            'tries' => $tries,
            'last_http_status' => $status,
            'last_error' => $giveUp ? $error.' Đã thử '.$tries.' lần.' : $error,
            'next_try_at' => $giveUp ? null : now()->addMinutes($schedule[$tries - 1]),
        ]);
        Log::warning('Gửi CRM chưa được', ['event_id' => $outbox->event_id, 'http' => $status, 'tries' => $tries]);
    }
}
