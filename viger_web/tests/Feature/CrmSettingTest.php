<?php

namespace Tests\Feature;

use App\Models\CrmSetting;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmSettingTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private const TOKEN = 'token-chatbot-test';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.crm_domain' => 'crm.viger.test',
            'crm.api_channels.1.token_hash' => hash('sha256', self::TOKEN),
        ]);
    }

    private function sendLead(string $phone): void
    {
        $this->postJson(self::CRM.'/api/v1/leads', [
            'schema_version' => 'lead/2',
            'event_id' => (string) Str::uuid(),
            'channel' => 'chatbot',
            'consent' => ['agreed' => true, 'text' => 'Đồng ý'],
            'contact' => ['phone' => $phone],
        ], ['X-Api-Token' => self::TOKEN])->assertCreated();
    }

    public function test_only_settings_permission_can_open(): void
    {
        $consultant = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'consultant']);
        $this->actingAs($consultant)->get(self::CRM.'/settings')->assertForbidden();

        $admin = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'admin']);
        $this->actingAs($admin)->get(self::CRM.'/settings')->assertOk()->assertSee('Cấu hình CRM')->assertSee('Kênh nhận khách')->assertDontSee('.env');
    }

    public function test_save_and_validate(): void
    {
        $admin = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'admin']);
        $base = ['idle_days' => 3, 'closed_days' => 14, 'auto_assign' => 'round_robin', 'auto_assign_role' => 'consultant'];

        $this->actingAs($admin)->put(self::CRM.'/settings', [...$base, 'idle_days' => 0, 'auto_assign_role' => 'super_admin'])
            ->assertSessionHasErrors(['idle_days', 'auto_assign_role']);

        $this->put(self::CRM.'/settings', $base)->assertRedirect(self::CRM.'/settings');
        $this->assertSame(3, CrmSetting::get('idle_days'));
        $this->assertSame(14, CrmSetting::get('closed_days'));

        $this->get(self::CRM.'/')->assertSee('3 ngày chưa có hoạt động');
        $this->get(self::CRM.'/pipeline')->assertSee('14 ngày gần nhất');
    }

    public function test_round_robin_assigns_new_api_leads_in_turn(): void
    {
        $a = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'consultant']);
        $b = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'consultant']);
        User::factory()->create(['active' => false, 'role' => 'USER', 'crm_role' => 'consultant']);

        // Mặc định tắt
        $this->sendLead('0911000001');
        $this->assertNull(Lead::firstWhere('phone', '0911000001')->assigned_to);

        CrmSetting::put(['auto_assign' => 'round_robin']);
        $this->sendLead('0911000002');
        $this->sendLead('0911000003');
        $this->sendLead('0911000004');

        $this->assertSame([$a->id, $b->id, $a->id], Lead::whereIn('phone', ['0911000002', '0911000003', '0911000004'])
            ->orderBy('phone')->pluck('assigned_to')->all());
        $this->assertStringContainsString('Tự động phân công', Lead::firstWhere('phone', '0911000002')->activities()->pluck('content')->implode("\n"));
    }
}
