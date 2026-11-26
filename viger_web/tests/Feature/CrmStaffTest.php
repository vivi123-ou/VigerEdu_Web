<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CrmStaffTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'admin', 'full_name' => 'Quản Trị']);
    }

    public function test_only_crm_admin_can_access_and_menu_hidden_for_others(): void
    {
        $consultant = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'consultant']);

        $this->actingAs($consultant)->get(self::CRM.'/staff')->assertForbidden();
        $this->get(self::CRM.'/')->assertDontSee('Quản lý nhân viên');

        $this->actingAs($this->admin)->get(self::CRM.'/staff')->assertOk()->assertSee('Quản lý nhân viên');
        $this->get(self::CRM.'/')->assertSee('href="'.self::CRM.'/staff"', false);
    }

    public function test_legacy_web_admin_is_crm_admin(): void
    {
        $legacy = User::factory()->create(['active' => true, 'role' => 'ADMIN', 'crm_role' => null]);

        $this->assertTrue($legacy->isCrmAdmin());
        $this->actingAs($legacy)->get(self::CRM.'/staff')->assertOk();
    }

    public function test_create_staff_with_temp_password_and_no_filament_access(): void
    {
        $res = $this->actingAs($this->admin)->post(self::CRM.'/staff', [
            'full_name' => 'Nguyễn Tư Vấn', 'username' => 'tuvan.nguyen', 'email' => 'tuvan@viger.edu.vn', 'crm_role' => 'consultant',
        ])->assertRedirect(self::CRM.'/staff')->assertSessionHas('temp_password');

        $temp = $res->getSession()->get('temp_password');
        $member = User::firstWhere('username', 'tuvan.nguyen');
        $this->assertSame('USER', $member->role);             // không vào được Filament
        $this->assertFalse($member->isAdmin());
        $this->assertSame('consultant', $member->crmRole());
        $this->assertTrue($member->must_change_password);
        $this->assertTrue(Hash::check($temp['password'], $member->password));

        $this->get(self::CRM.'/staff')->assertSee($temp['password']);
    }

    public function test_validation_unique_username_email_and_role(): void
    {
        User::factory()->create(['crm_role' => 'admin', 'username' => 'trung', 'email' => 'trung@viger.edu.vn']);

        $this->actingAs($this->admin)->post(self::CRM.'/staff', [
            'full_name' => 'X', 'username' => 'trung', 'email' => 'trung@viger.edu.vn', 'crm_role' => 'boss',
        ])->assertSessionHasErrors(['username', 'email', 'crm_role']);

        $this->post(self::CRM.'/staff', ['full_name' => 'X', 'username' => 'có dấu', 'email' => 'x@viger.edu.vn', 'crm_role' => 'consultant'])
            ->assertSessionHasErrors('username');
    }

    public function test_temp_password_forces_change_on_first_login(): void
    {
        $member = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'consultant', 'username' => 'nv1', 'password' => 'Tam12345', 'must_change_password' => true]);

        $this->post(self::CRM.'/login', ['username' => 'nv1', 'password' => 'Tam12345'])->assertRedirect(self::CRM);
        $this->get(self::CRM.'/')->assertRedirect(self::CRM.'/account/password');
        $this->get(self::CRM.'/leads')->assertRedirect(self::CRM.'/account/password');
        $this->get(self::CRM.'/account/password')->assertOk()->assertSee('mật khẩu tạm');

        // Mật khẩu yếu / trùng mật khẩu cũ bị từ chối
        $this->put(self::CRM.'/account/password', ['current_password' => 'Tam12345', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->put(self::CRM.'/account/password', ['current_password' => 'sai', 'password' => 'MoiMoi123', 'password_confirmation' => 'MoiMoi123'])
            ->assertSessionHasErrors('current_password');

        $this->put(self::CRM.'/account/password', ['current_password' => 'Tam12345', 'password' => 'MoiMoi123', 'password_confirmation' => 'MoiMoi123'])
            ->assertRedirect(self::CRM);

        $this->assertFalse($member->fresh()->must_change_password);
        $this->get(self::CRM.'/leads')->assertOk();
    }

    public function test_cannot_change_own_role_or_lock_self(): void
    {
        $this->actingAs($this->admin)->put(self::CRM."/staff/{$this->admin->id}", [
            'full_name' => 'Quản Trị Mới', 'username' => $this->admin->username, 'email' => $this->admin->email, 'crm_role' => 'staff',
        ])->assertRedirect();

        $this->assertSame('admin', $this->admin->fresh()->crmRole());
        $this->assertSame('Quản Trị Mới', $this->admin->fresh()->full_name);

        $this->post(self::CRM."/staff/{$this->admin->id}/lock")->assertStatus(422);
        $this->assertTrue($this->admin->fresh()->active);
    }

    public function test_lock_with_reassign_and_locked_user_is_logged_out(): void
    {
        $leaving = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'consultant', 'full_name' => 'Người Nghỉ']);
        $taker = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'consultant', 'full_name' => 'Người Nhận']);
        $open = Lead::factory()->create(['assigned_to' => $leaving->id, 'status' => 'consulting']);
        $closed = Lead::factory()->create(['assigned_to' => $leaving->id, 'status' => 'converted']);

        $this->actingAs($this->admin)->post(self::CRM."/staff/{$leaving->id}/lock", ['reassign_to' => $taker->id])
            ->assertSessionHas('success');

        $this->assertFalse($leaving->fresh()->active);
        $this->assertSame($taker->id, $open->fresh()->assigned_to);
        $this->assertSame($leaving->id, $closed->fresh()->assigned_to);   // khách đã chốt giữ nguyên
        $this->assertStringContainsString('Chuyển phụ trách từ Người Nghỉ sang Người Nhận', $open->activities()->pluck('content')->implode("\n"));

        // Người bị khoá đang đăng nhập => bị đăng xuất ở lần bấm kế tiếp
        $this->actingAs($leaving->fresh())->get(self::CRM.'/leads')->assertRedirect(self::CRM.'/login');
        $this->assertGuest();

        // Mở khoá
        $this->actingAs($this->admin)->post(self::CRM."/staff/{$leaving->id}/unlock");
        $this->assertTrue($leaving->fresh()->active);
    }

    public function test_reset_password(): void
    {
        $member = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'consultant', 'password' => 'CuCu12345']);

        $this->actingAs($this->admin)->post(self::CRM."/staff/{$member->id}/reset-password")->assertSessionHas('temp_password');

        $member->refresh();
        $this->assertTrue($member->must_change_password);
        $this->assertFalse(Hash::check('CuCu12345', $member->password));
    }

    public function test_header_shows_crm_role_label(): void
    {
        $staff = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'marketing']);

        $this->actingAs($staff)->get(self::CRM.'/')->assertSee('<small>Marketing</small>', false);
    }
}
