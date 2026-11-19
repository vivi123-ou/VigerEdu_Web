<?php

namespace App\Modules\CrmSync\Services;

use App\Modules\Assessment\Models\TestSession;
use App\Modules\CrmSync\Jobs\SendToCrm;
use App\Modules\CrmSync\Models\CrmOutbox;
use App\Modules\Tracking\Models\TrackingEvent;
use Illuminate\Support\Str;

/**
 * Làm gì:   Xếp gói sang CRM vào hàng đợi crm_outbox rồi giao cho job SendToCrm gửi nền (khách không phải chờ CRM).
 *           - queueLead(): khách vừa nhập đúng mã email → gói lead/2.
 *           - queueEvents(): hành vi mới sau khi CRM đã trả mã khách → gói events/1.
 *           Kết nối đang tắt / chưa đủ địa chỉ, token → gói nằm ở trạng thái "Đang tắt kết nối", không gửi request nào;
 *           bật lại thì resumeDisabled() chuyển sang "Chờ gửi" và gửi.
 * Ai gọi:   ReportAccessService (sau khi nhập đúng mã), Tracker, SendToCrm (sau khi lead thành công), trang "Gửi khách sang CRM".
 * Dữ liệu:  crm_outbox, tracking_events, respondents.crm_customer_code.
 */
class CrmSync
{
    public function __construct(private readonly CrmConfig $config, private readonly LeadPayload $lead) {}

    public function queueLead(TestSession $session): CrmOutbox
    {
        $eventId = (string) Str::uuid();
        $payload = $this->lead->build($session, $eventId);

        // Hành vi đã nằm trong gói lead: đánh dấu ngay để không gửi trùng bằng events/1 (gói lỗi vẫn được gửi lại nguyên vẹn).
        TrackingEvent::query()->whereIn('id', $payload[LeadPayload::TRACKING_IDS] ?? [])->update(['sent_at' => now()]);

        return $this->push($session, 'lead/2', $payload, $eventId);
    }

    /**
     * Gửi các hành vi chưa gửi của lượt làm bài (chỉ khi CRM đã trả mã khách; trước đó hành vi đi kèm gói lead).
     */
    public function queueEvents(TestSession $session): ?CrmOutbox
    {
        $code = $session->respondent?->crm_customer_code;
        if (blank($code)) {
            return null;
        }

        $events = TrackingEvent::query()->where('test_session_id', $session->id)->whereNull('sent_at')->orderBy('occurred_at')->orderBy('id')->get();
        if ($events->isEmpty()) {
            return null;
        }

        // Đánh dấu ngay để hai lần gọi liền nhau không gói trùng; gói lỗi vẫn nằm trong hàng đợi để gửi lại.
        TrackingEvent::query()->whereIn('id', $events->pluck('id'))->update(['sent_at' => now()]);
        $eventId = (string) Str::uuid();

        return $this->push($session, 'events/1', [
            'schema_version' => 'events/1',
            'event_id' => $eventId,
            'channel' => 'holland',
            'customer_code' => $code,
            'events' => $events->map(fn (TrackingEvent $e) => array_filter([
                'type' => $e->type, 'at' => $e->occurred_at->toIso8601String(), 'meta' => $e->meta ?: null,
            ]))->all(),
        ], $eventId);
    }

    /**
     * Bật lại kết nối: các gói "Đang tắt kết nối" chuyển sang "Chờ gửi" và được gửi.
     */
    public function resumeDisabled(): int
    {
        $ids = CrmOutbox::query()->where('status', CrmOutbox::DISABLED)->pluck('id');
        CrmOutbox::query()->whereIn('id', $ids)->update(['status' => CrmOutbox::PENDING, 'next_try_at' => now()]);
        $ids->each(fn (int $id) => SendToCrm::dispatch($id));

        return $ids->count();
    }

    /**
     * Gửi lại một gói lỗi / đang chờ (nút "Gửi lại" trên trang admin): giữ nguyên event_id.
     */
    public function retry(CrmOutbox $outbox): void
    {
        if ($outbox->status === CrmOutbox::SENT) {
            return;
        }
        $outbox->update([
            'status' => $this->config->ready() ? CrmOutbox::PENDING : CrmOutbox::DISABLED,
            'tries' => 0, 'next_try_at' => now(),
        ]);
        if ($outbox->status === CrmOutbox::PENDING) {
            SendToCrm::dispatch($outbox->id);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function push(TestSession $session, string $schema, array $payload, string $eventId): CrmOutbox
    {
        $ready = $this->config->ready();
        $outbox = CrmOutbox::query()->create([
            'event_id' => $eventId,
            'test_session_id' => $session->id,
            'schema' => $schema,
            'payload' => $payload,
            'status' => $ready ? CrmOutbox::PENDING : CrmOutbox::DISABLED,
            'next_try_at' => $ready ? now() : null,
        ]);

        if ($ready) {
            SendToCrm::dispatch($outbox->id)->afterCommit();
        }

        return $outbox;
    }
}
