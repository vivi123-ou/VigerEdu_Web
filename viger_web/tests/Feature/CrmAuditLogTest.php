<?php

namespace Tests\Feature;

use App\Models\CrmAuditLog;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.crm_domain' => 'crm.viger.test']);
        $this->admin = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'admin', 'username' => 'quantri', 'password' => 'MatKhau123']);
    }

    public function test_only_settings_permission_can_view(): void
    {
        $consultant = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'consultant']);
        $this->actingAs($consultant)->get(self::CRM.'/audit')->assertForbidden();
        $this->actingAs($this->admin)->get(self::CRM.'/audit')->assertOk()->assertSee('Nhật ký hệ thống');
    }

    public function test_login_success_and_failure_are_logged(): void
    {
        $this->post(self::CRM.'/login', ['username' => 'quantri', 'password' => 'sai']);
        $this->post(self::CRM.'/login', ['username' => 'quantri', 'password' => 'MatKhau123']);

        $this->assertSame(['login_failed', 'login'], CrmAuditLog::orderBy('id')->pluck('action')->all());
        $this->assertSame($this->admin->id, CrmAuditLog::firstWhere('action', 'login')->user_id);
    }

    public function test_delete_and_export_logged_without_customer_pii(): void
    {
        $lead = Lead::factory()->create(['full_name' => 'Phụ Huynh Bí Mật', 'phone' => '0909111222']);

        $this->actingAs($this->admin)->delete(self::CRM."/leads/{$lead->id}");
        $this->get(self::CRM.'/leads/export?status=new');

        $delete = CrmAuditLog::firstWhere('action', 'leads.delete');
        $this->assertStringContainsString($lead->code(), $delete->description);
        $this->assertStringNotContainsString('Bí Mật', $delete->description);
        $this->assertStringNotContainsString('0909111222', $delete->description);
        $this->assertStringContainsString('trạng thái: Mới', CrmAuditLog::firstWhere('action', 'leads.export')->description);
    }

    public function test_role_staff_and_settings_changes_are_logged_and_filterable(): void
    {
        $member = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'consultant', 'username' => 'tuvan1']);
        $super = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'super_admin']);

        $this->actingAs($this->admin)->post(self::CRM."/staff/{$member->id}/lock");
        $this->put(self::CRM.'/settings', ['idle_days' => 5, 'closed_days' => 30, 'auto_assign' => 'off', 'auto_assign_role' => 'consultant']);

        $this->actingAs($super)->put(self::CRM.'/roles', [
            'perms' => ['admin' => ['dashboard.view'], 'manager' => [], 'consultant' => [], 'marketing' => []],
        ]);

        $this->assertStringContainsString('Khoá tài khoản tuvan1', CrmAuditLog::firstWhere('action', 'staff.lock')->description);
        $this->assertStringContainsString('Nhắc chăm sóc lại sau (ngày): 7 → 5', CrmAuditLog::firstWhere('action', 'settings.update')->description);
        $this->assertStringContainsString('bỏ quyền Xem Dashboard', CrmAuditLog::firstWhere('action', 'roles.update')->description);

        $this->get(self::CRM.'/audit?action=staff.lock')->assertOk()
            ->assertSee('Khoá tài khoản tuvan1')->assertDontSee('Nhắc chăm sóc lại sau');
    }
}
