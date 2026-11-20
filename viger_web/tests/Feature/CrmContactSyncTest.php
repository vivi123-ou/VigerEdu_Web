<?php

namespace Tests\Feature;

use App\Jobs\SendContactToCrm;
use App\Models\ContactMessage;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Form liên hệ web chính → API CRM (HOP_DONG_KET_NOI mục 2.1 & 4).
 */
class CrmContactSyncTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private const TOKEN = 'token-website-test';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.crm.url' => self::CRM.'/api/v1',
            'services.crm.token' => self::TOKEN,
            'crm.api_channels.0.token_hash' => hash('sha256', self::TOKEN),
        ]);
    }

    private function payload(array $override = []): array
    {
        return array_replace_recursive([
            'schema_version' => 'lead/2',
            'event_id' => (string) Str::uuid(),
            'channel' => 'website',
            'consent' => ['agreed' => true, 'text' => 'Bấm "Gửi" là bạn đồng ý...'],
            'contact' => ['full_name' => 'Nguyễn Văn A', 'phone' => '+84901234567', 'email' => 'a@example.com'],
            'extra_fields' => ['loi_nhan' => 'Con tôi muốn du học nghề.'],
        ], $override);
    }

    private function api(array $payload, ?string $token = self::TOKEN)
    {
        return $this->postJson(self::CRM.'/api/v1/leads', $payload, $token ? ['X-Api-Token' => $token] : []);
    }

    // ---------- Phía web chính ----------

    public function test_contact_form_saves_message_and_queues_crm_job(): void
    {
        Queue::fake();

        $this->post('/lien-he', ['name' => 'Phụ Huynh', 'email' => 'ph@example.com', 'phone' => '0901 234 567', 'message' => 'Tư vấn giúp'])
            ->assertRedirect();

        $message = ContactMessage::sole();
        $this->assertStringContainsString('đồng ý cho VIGER EDU', $message->consent_text);
        Queue::assertPushed(SendContactToCrm::class, fn ($job) => $job->message->is($message));
    }

    public function test_job_sends_lead2_payload_and_marks_synced(): void
    {
        Http::fake(['*/api/v1/leads' => Http::response(['lead_code' => 'VIGER-LEAD-00007', 'is_new' => true], 201)]);
        $message = ContactMessage::create(['name' => 'Phụ Huynh', 'email' => 'ph@example.com', 'phone' => '0901 234 567', 'message' => 'Tư vấn giúp', 'consent_text' => 'Đồng ý']);

        (new SendContactToCrm($message))->handle();

        Http::assertSent(fn (Request $r) => $r->hasHeader('X-Api-Token', self::TOKEN)
            && $r['schema_version'] === 'lead/2'
            && $r['channel'] === 'website'
            && $r['contact']['phone'] === '+84901234567'
            && $r['consent']['agreed'] === true
            && $r['extra_fields']['loi_nhan'] === 'Tư vấn giúp'
            && Str::isUuid($r['event_id']));

        $message->refresh();
        $this->assertSame('VIGER-LEAD-00007', $message->crm_lead_code);
        $this->assertNotNull($message->crm_synced_at);
    }

    public function test_job_retries_when_crm_is_down_and_keeps_same_event_id(): void
    {
        // Lần 1: CRM lỗi 503; lần 2: CRM nhận
        Http::fake(['*' => Http::sequence()->push('down', 503)->push(['lead_code' => 'X'], 200)]);
        $message = ContactMessage::create(['name' => 'A', 'email' => 'a@example.com', 'phone' => '0901234567', 'consent_text' => 'Đồng ý']);

        try {
            (new SendContactToCrm($message))->handle();
            $this->fail('Phải ném lỗi để hàng đợi thử lại');
        } catch (RuntimeException) {
        }

        $eventId = $message->fresh()->crm_event_id;
        $this->assertNotNull($eventId);
        $this->assertNull($message->fresh()->crm_synced_at);

        // Lần thử lại dùng đúng event_id cũ => CRM không tạo trùng
        (new SendContactToCrm($message->fresh()))->handle();
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => $r['event_id'] === $eventId);
        $this->assertNotNull($message->fresh()->crm_synced_at);
    }

    public function test_job_does_nothing_when_crm_not_configured(): void
    {
        config(['services.crm.url' => null]);
        Http::fake();
        $message = ContactMessage::create(['name' => 'A', 'email' => 'a@example.com', 'phone' => '0901234567', 'consent_text' => 'x']);

        (new SendContactToCrm($message))->handle();

        Http::assertNothingSent();
    }

    // ---------- Phía CRM (API) ----------

    public function test_api_rejects_bad_token_and_wrong_channel(): void
    {
        $this->api($this->payload(), null)->assertStatus(401);
        $this->api($this->payload(), 'sai-token')->assertStatus(401);
        $this->api($this->payload(['channel' => 'holland']))->assertStatus(403);

        config(['crm.api_channels.0.enabled' => false]);
        $this->api($this->payload())->assertStatus(403);
    }

    public function test_api_validation_returns_field_names_only(): void
    {
        $res = $this->api($this->payload(['consent' => ['agreed' => false], 'contact' => ['phone' => 'abc', 'email' => 'không-phải-email']]));

        $res->assertStatus(422)->assertJsonValidationErrors(['consent.agreed', 'contact.phone', 'contact.email']);
        $this->assertStringNotContainsString('không-phải-email', $res->getContent());
    }

    public function test_api_creates_new_lead_and_is_idempotent(): void
    {
        $payload = $this->payload();

        $first = $this->api($payload)->assertCreated()->assertJson(['is_new' => true])->json();

        $lead = Lead::sole();
        $this->assertSame('0901234567', $lead->phone);
        $this->assertSame('website', $lead->source);
        $this->assertSame('Con tôi muốn du học nghề.', $lead->note);
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'form', 'content' => 'Con tôi muốn du học nghề.']);

        // Gửi lại cùng event_id => kết quả cũ, không tạo thêm
        $this->api($payload)->assertOk()->assertExactJson($first);
        $this->assertSame(1, Lead::count());
        $this->assertSame(1, $lead->activities()->where('type', 'form')->count());
    }

    public function test_api_matches_existing_lead_by_phone_without_overwriting(): void
    {
        $lead = Lead::factory()->create(['full_name' => 'Tên Cũ', 'phone' => '0901234567', 'email' => 'cu@example.com', 'note' => 'Ghi chú cũ']);

        $this->api($this->payload(['contact' => ['full_name' => 'Tên Mới']]))
            ->assertOk()
            ->assertJson(['is_new' => false, 'lead_code' => $lead->code()]);

        $lead->refresh();
        $this->assertSame('Tên Cũ', $lead->full_name);
        $this->assertSame('cu@example.com', $lead->email);
        $this->assertSame('Ghi chú cũ', $lead->note);
        $this->assertStringContainsString('Khách quay lại', $lead->activities()->pluck('content')->implode("\n"));
        $this->assertStringContainsString('tên trên form: Tên Mới', $lead->activities()->pluck('content')->implode("\n"));
    }
}
