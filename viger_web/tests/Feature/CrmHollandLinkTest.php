<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Nối khách đang tư vấn với Holland / Website: link trắc nghiệm gắn mã khách, events/1, ping, link bài làm.
 */
class CrmHollandLinkTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private const TOKEN = 'token-holland-test';

    private const WEB_TOKEN = 'token-website-test';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.crm_domain' => 'crm.viger.test',
            'crm.holland_url' => 'https://holland.viger.test',
            'crm.api_channels.0.token_hash' => hash('sha256', self::WEB_TOKEN),
            'crm.api_channels.2.token_hash' => hash('sha256', self::TOKEN),
        ]);
    }

    private function hollandLead(array $override = []): array
    {
        return array_replace_recursive([
            'schema_version' => 'lead/2',
            'event_id' => (string) Str::uuid(),
            'channel' => 'holland',
            'consent' => ['agreed' => true, 'text' => 'Đồng ý'],
            'contact' => ['email' => 'hs@example.com'],
            'holland_result' => ['schema_version' => 'holland-result/3', 'scores' => ['R' => 2, 'I' => 4.5, 'A' => 4, 'S' => 3, 'E' => 2, 'C' => 1.5], 'code_full' => 'IAS'],
            'quiz' => ['view_url' => 'https://holland.viger.test/admin/bao-cao?ma=abc'],
        ], $override);
    }

    public function test_invite_link_attaches_result_to_existing_lead_even_with_different_contact(): void
    {
        $lead = Lead::factory()->create(['phone' => '0901000001', 'email' => 'phuhuynh@example.com']);
        $admin = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'admin']);

        $this->assertSame('https://holland.viger.test/?utm_source=crm&utm_medium=tu_van&utm_content='.$lead->inviteRef(), $lead->hollandInviteUrl());
        $this->actingAs($admin)->get(self::CRM."/leads/{$lead->id}")->assertSee('Chép link trắc nghiệm cho khách');

        // Mã đoán bừa (không có chữ ký) => không gắn vào hồ sơ, tạo khách mới theo email
        $this->postJson(self::CRM.'/api/v1/leads', $this->hollandLead([
            'contact' => ['email' => 'la@example.com'], 'utm' => ['content' => $lead->code()],
        ]), ['X-Api-Token' => self::TOKEN])->assertCreated();
        $this->assertNull($lead->fresh()->holland_report_url);
        Lead::where('email', 'la@example.com')->delete();

        // Học sinh làm bài bằng email riêng, nhưng mở từ link CRM gửi => utm.content mang mã khách
        $this->postJson(self::CRM.'/api/v1/leads', $this->hollandLead([
            'utm' => ['source' => 'crm', 'medium' => 'tu_van', 'content' => $lead->inviteRef()],
        ]), ['X-Api-Token' => self::TOKEN])->assertOk()->assertJsonPath('is_new', false);

        $this->assertSame(1, Lead::count());
        $lead->refresh();
        $this->assertSame('IAS', $lead->holland_code ?? $lead->hollandCode());
        $this->assertSame('https://holland.viger.test/admin/bao-cao?ma=abc', $lead->holland_report_url);

        $this->get(self::CRM."/leads/{$lead->id}")->assertSee('Xem bài làm trên Holland');
    }

    public function test_customer_code_matches_first_and_lead_events_are_logged(): void
    {
        $lead = Lead::factory()->create();

        $this->postJson(self::CRM.'/api/v1/leads', $this->hollandLead([
            'customer_code' => $lead->code(),
            'events' => [['type' => 'quiz_finished', 'at' => now()->subMinutes(5)->toIso8601String()], ['type' => 'cta_zalo_clicked']],
        ]), ['X-Api-Token' => self::TOKEN])->assertOk();

        $log = $lead->activities()->where('type', 'tracking')->pluck('content')->implode("\n");
        $this->assertStringContainsString('Làm xong trắc nghiệm Holland', $log);
        $this->assertStringContainsString('Bấm nút Zalo — nên liên hệ lại sớm', $log);
    }

    public function test_events_endpoint_is_idempotent_and_rejects_unknown_customer(): void
    {
        $lead = Lead::factory()->create();
        $payload = [
            'schema_version' => 'events/1', 'event_id' => (string) Str::uuid(), 'channel' => 'holland',
            'customer_code' => $lead->code(), 'events' => [['type' => 'report_viewed', 'meta' => ['device' => 'mobile']]],
        ];

        $this->postJson(self::CRM.'/api/v1/events', $payload, ['X-Api-Token' => self::TOKEN])->assertOk()->assertJsonPath('accepted', 1);
        $this->postJson(self::CRM.'/api/v1/events', $payload, ['X-Api-Token' => self::TOKEN])->assertOk();
        $this->assertSame(1, $lead->activities()->where('type', 'tracking')->count());

        $this->postJson(self::CRM.'/api/v1/events', [...$payload, 'event_id' => (string) Str::uuid(), 'customer_code' => 'VIGER-LEAD-99999'], ['X-Api-Token' => self::TOKEN])
            ->assertStatus(422);
        $this->postJson(self::CRM.'/api/v1/events', $payload, ['X-Api-Token' => 'sai'])->assertUnauthorized();
    }

    public function test_website_can_send_events_and_ping_works(): void
    {
        $lead = Lead::factory()->create();

        $this->postJson(self::CRM.'/api/v1/events', [
            'schema_version' => 'events/1', 'event_id' => (string) Str::uuid(), 'channel' => 'website',
            'customer_code' => $lead->code(), 'events' => [['type' => 'cta_call_clicked']],
        ], ['X-Api-Token' => self::WEB_TOKEN])->assertOk();

        $this->getJson(self::CRM.'/api/v1/ping', ['X-Api-Token' => self::TOKEN])->assertOk()->assertJson(['ok' => true, 'channel' => 'holland']);
        $this->getJson(self::CRM.'/api/v1/ping')->assertUnauthorized();
    }
}
