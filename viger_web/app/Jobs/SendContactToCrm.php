<?php

namespace App\Jobs;

use App\Models\ContactMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Làm gì:   Gửi một form liên hệ sang CRM theo gói lead/2 (HOP_DONG_KET_NOI mục 2.1 & 4).
 * Ai gọi:   ContactController@store (đưa vào hàng đợi); lệnh `crm:resend-contacts` (gửi lại).
 * Dữ liệu:  đọc contact_messages; ghi crm_event_id / crm_lead_code / crm_synced_at.
 *           Không ghi SĐT, email, họ tên vào log.
 *
 * CRM lỗi mạng / 5xx / 429 => ném lỗi để hàng đợi thử lại (backoff tăng dần).
 * 401 / 403 / 422 => sai cấu hình hoặc dữ liệu, thử lại vô ích => đánh dấu thất bại.
 */
class SendContactToCrm implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    /** Thử lại sau 1 phút, 5 phút, 15 phút, 1 giờ, 3 giờ */
    public array $backoff = [60, 300, 900, 3600, 10800];

    public function __construct(public ContactMessage $message) {}

    public function handle(): void
    {
        $url = config('services.crm.url');
        $token = config('services.crm.token');

        // Chưa cấu hình kết nối CRM => bỏ qua; form vẫn nằm trong contact_messages để gửi lại sau.
        if (! $url || ! $token || $this->message->crm_synced_at) {
            return;
        }

        // event_id cố định cho form này => gửi lại nhiều lần CRM không tạo trùng
        if (! $this->message->crm_event_id) {
            $this->message->forceFill(['crm_event_id' => (string) Str::uuid()])->save();
        }

        try {
            $response = Http::withHeaders(['X-Api-Token' => $token])
                ->acceptJson()
                ->timeout(10)
                ->post(rtrim($url, '/').'/leads', $this->payload());
        } catch (ConnectionException $e) {
            throw new RuntimeException("CRM không phản hồi (contact_message #{$this->message->id}).", previous: $e);
        }

        if ($response->successful()) {
            $this->message->forceFill([
                'crm_lead_code' => $response->json('lead_code'),
                'crm_synced_at' => now(),
            ])->save();

            return;
        }

        if (in_array($response->status(), [401, 403, 422], true)) {
            // Chỉ log tên trường lỗi, không log giá trị
            Log::warning('CRM từ chối form liên hệ', [
                'contact_message_id' => $this->message->id,
                'status' => $response->status(),
                'fields' => array_keys((array) $response->json('errors')),
            ]);
            $this->fail(new RuntimeException("CRM trả {$response->status()} cho contact_message #{$this->message->id}."));

            return;
        }

        throw new RuntimeException("CRM lỗi {$response->status()} (contact_message #{$this->message->id}), sẽ thử lại.");
    }

    /** Gói lead/2 — chỉ gửi trường có giá trị. */
    public function payload(): array
    {
        $m = $this->message;

        return array_filter([
            'schema_version' => 'lead/2',
            'event_id' => $m->crm_event_id,
            'channel' => 'website',
            'external_ref' => 'contact-'.$m->id,
            // Form cũ (trước khi có câu đồng ý) => agreed false, CRM sẽ từ chối 422 đúng hợp đồng
            'consent' => [
                'agreed' => (bool) $m->consent_text,
                'version' => config('services.crm.consent_version'),
                'agreed_at' => $m->created_at->toIso8601String(),
                'text' => $m->consent_text,
            ],
            'contact' => array_filter([
                'full_name' => $m->name,
                'phone' => self::normalizePhone($m->phone),
                'email' => $m->email,
            ]),
            'extra_fields' => array_filter(['loi_nhan' => $m->message]) ?: null,
            'utm' => ['source' => 'viger.edu.vn', 'medium' => 'contact_form'],
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /** 0901 234 567 / 84901234567 / +84901234567 => +84901234567 */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return match (true) {
            $digits === '' => null,
            str_starts_with($digits, '84') => '+'.$digits,
            str_starts_with($digits, '0') => '+84'.substr($digits, 1),
            default => '+'.$digits,
        };
    }
}
