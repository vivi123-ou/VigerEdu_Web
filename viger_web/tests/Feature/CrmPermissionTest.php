<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\CrmPermission;
use App\Models\CrmRole;
use App\Models\CrmTeam;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vai trò & phân quyền: phạm vi dữ liệu từng vai trò, chặn URL (403), ẩn menu, ma trận sửa được.
 */
class CrmPermissionTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $super;

    private User $admin;

    private User $manager;

    private User $teamMate;

    private User $consultant;

    private User $outsider;

    private User $marketing;

    private Lead $myLead;

    private Lead $teamLead;

    private Lead $otherLead;

    private Lead $unassigned;

    protected function setUp(): void
    {
        parent::setUp();

        $mk = fn (string $role, string $name) => User::factory()->create(['crm_role' => $role, 'role' => 'USER', 'active' => true, 'full_name' => $name]);
        $this->super = $mk('super_admin', 'Super');
        $this->admin = $mk('admin', 'Admin');
        $this->manager = $mk('manager', 'Trưởng Nhóm');
        $this->consultant = $mk('consultant', 'Tư Vấn Trong Nhóm');
        $this->teamMate = $mk('consultant', 'Đồng Nghiệp Trong Nhóm');
        $this->outsider = $mk('consultant', 'Tư Vấn Nhóm Khác');
        $this->marketing = $mk('marketing', 'Marketing');

        $team = CrmTeam::create(['name' => 'Nhóm Hà Nội', 'manager_id' => $this->manager->id]);
        $this->consultant->update(['crm_team_id' => $team->id]);
        $this->teamMate->update(['crm_team_id' => $team->id]);

        $this->myLead = Lead::factory()->create(['full_name' => 'Khách Của Tôi', 'assigned_to' => $this->consultant->id, 'status' => 'new']);
        $this->teamLead = Lead::factory()->create(['full_name' => 'Khách Đồng Nghiệp', 'assigned_to' => $this->teamMate->id, 'status' => 'new']);
        $this->otherLead = Lead::factory()->create(['full_name' => 'Khách Nhóm Khác', 'assigned_to' => $this->outsider->id, 'status' => 'new']);
        $this->unassigned = Lead::factory()->create(['full_name' => 'Khách Chưa Giao', 'assigned_to' => null, 'status' => 'new']);
    }

    private function row(string $name): string
    {
        return 'class="link-strong">'.$name.'</a>';
    }

    // ---------------- Consultant ----------------

    public function test_consultant_sees_only_assigned_leads(): void
    {
        $this->actingAs($this->consultant);

        $this->get(self::CRM.'/leads')->assertOk()
            ->assertSee($this->row('Khách Của Tôi'), false)
            ->assertDontSee($this->row('Khách Đồng Nghiệp'), false)
            ->assertDontSee($this->row('Khách Chưa Giao'), false);

        $customers = $this->get(self::CRM.'/customers')->viewData('customers');
        $this->assertSame(['Khách Của Tôi'], $customers->pluck('full_name')->all());

        $this->assertSame(1, $this->get(self::CRM.'/pipeline')->viewData('columns')['new']['total']);
    }

    public function test_consultant_blocked_from_other_leads_by_url(): void
    {
        $this->actingAs($this->consultant);
        $id = $this->teamLead->id;

        $this->get(self::CRM."/leads/{$id}")->assertForbidden();
        $this->get(self::CRM."/leads/{$id}/edit/parent")->assertForbidden();
        $this->put(self::CRM."/leads/{$id}/edit/general", ['source' => 'website', 'status' => 'lost'])->assertForbidden();
        $this->patchJson(self::CRM."/pipeline/{$id}", ['status' => 'consulting'])->assertForbidden();
        $this->post(self::CRM."/leads/{$id}/notes", ['type' => 'note', 'content' => 'x'])->assertForbidden();
        $this->post(self::CRM.'/notes', ['lead_id' => $id, 'type' => 'note', 'content' => 'x'])->assertForbidden();

        $appt = Appointment::create(['lead_id' => $id, 'user_id' => $this->teamMate->id, 'scheduled_at' => now()->addDay(), 'duration_minutes' => 30, 'type' => 'call', 'status' => 'scheduled']);
        $this->get(self::CRM."/appointments/{$appt->id}/edit")->assertForbidden();
        $this->post(self::CRM.'/appointments', [
            'lead_id' => $id, 'scheduled_at' => now()->addDays(2)->format('Y-m-d\TH:i'), 'duration_minutes' => 30, 'type' => 'call',
        ])->assertSessionHasErrors('lead_id');

        $this->assertSame('new', $this->teamLead->fresh()->status);

        // Khách của mình thì làm được
        $this->get(self::CRM."/leads/{$this->myLead->id}")->assertOk();
        $this->patchJson(self::CRM."/pipeline/{$this->myLead->id}", ['status' => 'consulting'])->assertOk();
    }

    public function test_consultant_cannot_delete_assign_or_export(): void
    {
        $this->actingAs($this->consultant);

        $this->delete(self::CRM."/leads/{$this->myLead->id}")->assertForbidden();
        $this->get(self::CRM.'/leads/export')->assertForbidden();
        $this->get(self::CRM."/leads/{$this->myLead->id}")->assertDontSee('Xoá khách hàng');

        // Sửa thông tin chung: không đổi được người phụ trách
        $this->put(self::CRM."/leads/{$this->myLead->id}/edit/general", [
            'source' => 'website', 'status' => 'consulting', 'assigned_to' => $this->outsider->id,
        ]);
        $this->assertSame($this->consultant->id, $this->myLead->fresh()->assigned_to);
        $this->assertSame('consulting', $this->myLead->fresh()->status);   // phần còn lại vẫn lưu
    }

    public function test_consultant_created_lead_is_auto_assigned_to_self(): void
    {
        $this->actingAs($this->consultant)->post(self::CRM.'/leads', [
            'full_name' => 'Khách Tự Nhập', 'phone' => '0987000111', 'source' => 'other', 'status' => 'new',
            'assigned_to' => $this->outsider->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->consultant->id, Lead::firstWhere('phone', '0987000111')->assigned_to);
    }

    public function test_consultant_menu_hides_system_and_sources(): void
    {
        $html = $this->actingAs($this->consultant)->get(self::CRM.'/')->getContent();

        $this->assertStringNotContainsString('href="'.self::CRM.'/staff"', $html);
        $this->assertStringNotContainsString('href="'.self::CRM.'/roles"', $html);
        $this->assertStringNotContainsString('href="'.self::CRM.'/sources/website"', $html);
        $this->assertStringContainsString('href="'.self::CRM.'/leads"', $html);

        $this->get(self::CRM.'/staff')->assertForbidden();
        $this->get(self::CRM.'/roles')->assertForbidden();
        $this->get(self::CRM.'/sources/website')->assertForbidden();
    }

    public function test_consultant_reports_and_dashboard_are_personal(): void
    {
        $this->actingAs($this->consultant);

        $stats = $this->get(self::CRM.'/')->viewData('stats');
        $this->assertSame(1, $stats[0]['value']);   // Tổng leads: chỉ khách của mình

        $rows = $this->get(self::CRM.'/reports/consultants')->viewData('rows');
        $this->assertSame([$this->consultant->id], $rows->pluck('user.id')->all());
    }

    public function test_consultant_notifications_only_from_assigned_leads(): void
    {
        $this->myLead->log('Ghi chú khách tôi', 'note', $this->admin->id);
        $this->teamLead->log('Ghi chú khách đồng nghiệp', 'note', $this->admin->id);

        $this->actingAs($this->consultant)->get(self::CRM.'/leads')
            ->assertSee('Ghi chú khách tôi')
            ->assertDontSee('Ghi chú khách đồng nghiệp');
    }

    // ---------------- Manager ----------------

    public function test_manager_sees_team_and_unassigned_only(): void
    {
        $names = $this->actingAs($this->manager)->get(self::CRM.'/customers')->viewData('customers')->pluck('full_name')->sort()->values()->all();

        $this->assertSame(['Khách Chưa Giao', 'Khách Của Tôi', 'Khách Đồng Nghiệp'], $names);
        $this->get(self::CRM."/leads/{$this->otherLead->id}")->assertForbidden();
    }

    public function test_manager_assigns_only_within_team(): void
    {
        $this->actingAs($this->manager);

        $this->put(self::CRM."/leads/{$this->unassigned->id}/edit/general", ['source' => 'website', 'status' => 'new', 'assigned_to' => $this->teamMate->id])
            ->assertSessionHasNoErrors();
        $this->assertSame($this->teamMate->id, $this->unassigned->fresh()->assigned_to);

        $this->put(self::CRM."/leads/{$this->myLead->id}/edit/general", ['source' => 'website', 'status' => 'new', 'assigned_to' => $this->outsider->id])
            ->assertSessionHasErrors('assigned_to');
    }

    public function test_manager_manages_only_team_members_with_lower_roles(): void
    {
        $this->actingAs($this->manager);

        $staff = $this->get(self::CRM.'/staff')->assertOk()->viewData('staff')->pluck('full_name')->all();
        $this->assertEqualsCanonicalizing(['Trưởng Nhóm', 'Tư Vấn Trong Nhóm', 'Đồng Nghiệp Trong Nhóm'], $staff);

        $this->get(self::CRM."/staff/{$this->outsider->id}/edit")->assertForbidden();
        $this->post(self::CRM."/staff/{$this->admin->id}/lock")->assertForbidden();

        // Không cấp được vai trò ngang / cao hơn
        $this->post(self::CRM.'/staff', ['full_name' => 'X', 'username' => 'x.manager', 'email' => 'x@viger.edu.vn', 'crm_role' => 'manager'])
            ->assertSessionHasErrors('crm_role');

        // Tạo Consultant => tự vào nhóm của Manager
        $this->post(self::CRM.'/staff', ['full_name' => 'Người Mới', 'username' => 'nguoi.moi', 'email' => 'moi@viger.edu.vn', 'crm_role' => 'consultant'])
            ->assertSessionHasNoErrors();
        $this->assertSame($this->consultant->crm_team_id, User::firstWhere('username', 'nguoi.moi')->crm_team_id);
    }

    // ---------------- Marketing ----------------

    public function test_marketing_sees_aggregates_but_no_customer_records(): void
    {
        $this->actingAs($this->marketing);

        $this->get(self::CRM.'/leads')->assertForbidden();
        $this->get(self::CRM.'/customers')->assertForbidden();
        $this->get(self::CRM."/leads/{$this->myLead->id}")->assertForbidden();

        $dash = $this->get(self::CRM.'/')->assertOk()->assertDontSee('Khách Của Tôi');
        $this->assertSame(4, $dash->viewData('stats')[0]['value']);   // số tổng hợp toàn bộ

        $this->get(self::CRM.'/sources/website')->assertOk()
            ->assertSee('Bạn chỉ xem được số liệu tổng hợp')
            ->assertDontSee('Khách Của Tôi');

        $this->get(self::CRM.'/reports/customers')->assertOk();
    }

    // ---------------- Admin / Super Admin & ma trận ----------------

    public function test_admin_sees_everything_but_roles_page_is_super_admin_only(): void
    {
        $this->assertCount(4, $this->actingAs($this->admin)->get(self::CRM.'/customers')->viewData('customers'));
        $this->get(self::CRM.'/roles')->assertForbidden();

        $this->actingAs($this->super)->get(self::CRM.'/roles')->assertOk()
            ->assertSee('Ma trận phân quyền')
            ->assertSee('leads.delete');
    }

    public function test_matrix_change_takes_effect_and_super_admin_is_locked(): void
    {
        $this->actingAs($this->super);

        // Gỡ quyền "Thêm khách hàng" của Consultant, mở phạm vi toàn bộ
        $matrix = CrmRole::with('permissions')->get()->mapWithKeys(fn ($r) => [$r->key => $r->permissionKeys()])->all();
        $matrix['consultant'] = array_values(array_diff($matrix['consultant'], ['leads.create']));
        $matrix['super_admin'] = [];   // cố tình gửi rỗng — phải bị bỏ qua

        $this->put(self::CRM.'/roles', ['perms' => $matrix, 'scope' => ['consultant' => 'all']])
            ->assertRedirect(self::CRM.'/roles')->assertSessionHas('success');

        $this->actingAs($this->consultant->fresh());
        $this->get(self::CRM.'/leads/create')->assertForbidden();
        $this->assertCount(4, $this->get(self::CRM.'/customers')->viewData('customers'));

        // Super Admin vẫn toàn quyền
        $this->assertTrue($this->super->fresh()->crmCan('leads.create'));
        $this->assertTrue($this->super->fresh()->crmCan('roles.manage'));
    }

    public function test_cannot_remove_roles_manage_from_own_role(): void
    {
        // Admin được cấp roles.manage, rồi thử tự gỡ
        $admin = CrmRole::firstWhere('key', 'admin');
        $admin->permissions()->attach(CrmPermission::firstWhere('key', 'roles.manage'));

        $this->actingAs($this->admin)->put(self::CRM.'/roles', ['perms' => ['admin' => ['dashboard.view']]]);

        $this->assertTrue($this->admin->fresh()->crmCan('roles.manage'));
    }

    public function test_teams_crud(): void
    {
        $this->actingAs($this->super);

        $this->post(self::CRM.'/roles/teams', ['name' => 'Nhóm HCM', 'manager_id' => $this->admin->id])->assertSessionHasNoErrors();
        $team = CrmTeam::firstWhere('name', 'Nhóm HCM');
        $this->post(self::CRM.'/roles/teams', ['name' => 'Nhóm HCM'])->assertSessionHasErrors('name');

        $this->put(self::CRM."/roles/teams/{$team->id}", ['name' => 'Nhóm Sài Gòn', 'manager_id' => null])->assertSessionHasNoErrors();
        $this->assertSame('Nhóm Sài Gòn', $team->fresh()->name);

        $this->outsider->update(['crm_team_id' => $team->id]);
        $this->delete(self::CRM."/roles/teams/{$team->id}");
        $this->assertNull($this->outsider->fresh()->crm_team_id);
    }

    public function test_legacy_roles_mapping(): void
    {
        $webAdmin = User::factory()->create(['role' => 'ADMIN', 'crm_role' => null]);
        $plain = User::factory()->create(['role' => 'USER', 'crm_role' => null]);

        $this->assertSame('super_admin', $webAdmin->crmRole());
        $this->assertSame('consultant', $plain->crmRole());
        $this->assertSame('own', $plain->crmScope());
    }
}
