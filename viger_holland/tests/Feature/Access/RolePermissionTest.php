<?php

namespace Tests\Feature\Access;

use App\Models\User;
use App\Modules\Access\Models\Permission;
use App\Modules\Access\Models\Role;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * H1: phân quyền nằm trong bảng roles / permissions (Quản trị viên > Tư vấn viên > Nhân viên, Marketing).
 * Mỗi vai trò chỉ thấy mục menu có quyền; gõ thẳng địa chỉ cũng bị chặn. Chỉ quản trị viên xuất dữ liệu.
 */
class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SettingSeeder::class, RoleSeeder::class, TestConfigSeeder::class]);
    }

    public function test_default_roles_have_the_agreed_permissions(): void
    {
        $permissions = fn (string $code) => Role::where('code', $code)->first()->permissions->pluck('code')->sort()->values()->all();

        $this->assertCount(12, $permissions('ADMIN'));
        $this->assertSame(['attempts.view', 'dashboard.view'], $permissions('COUNSELOR'));
        $this->assertSame(['dashboard.view'], $permissions('STAFF'));
        $this->assertSame(['content.manage', 'dashboard.view'], $permissions('EDITOR'));
        $this->assertFalse(Role::where('code', 'EDITOR')->value('is_system'), 'Marketing là vai trò thêm, quản trị viên xóa được');
    }

    public function test_only_admin_can_export_data(): void
    {
        $this->assertTrue($this->user('ADMIN')->canExportData());

        foreach (['COUNSELOR', 'STAFF', 'EDITOR'] as $role) {
            $this->assertFalse($this->user($role)->canExportData(), "{$role} không được xuất dữ liệu");
        }
    }

    public function test_each_role_reaches_only_its_menu_items(): void
    {
        $this->actingAs($this->user('COUNSELOR'));
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Lượt làm bài')->assertDontSee('Cài đặt bài test')->assertDontSee('Thư viện ảnh');
        $this->get(route('admin.test-settings.edit'))->assertForbidden();
        $this->get(route('admin.media.index'))->assertForbidden();

        $this->actingAs($this->user('STAFF'));
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Lượt làm bài')->assertDontSee('Cài đặt bài test');
        $this->get(route('admin.section', 'attempts'))->assertForbidden();

        $this->actingAs($this->user('EDITOR'));
        $this->get(route('admin.media.index'))->assertOk();
        $this->get(route('admin.test-settings.edit'))->assertForbidden();
        $this->put(route('admin.test-settings.update'), ['items_per_page' => 3])->assertForbidden();
    }

    public function test_admin_can_grant_a_permission_to_a_role_without_code_changes(): void
    {
        $counselor = $this->user('COUNSELOR');
        $this->actingAs($counselor)->get(route('admin.test-settings.edit'))->assertForbidden();

        Role::where('code', 'COUNSELOR')->first()->permissions()->attach(Permission::where('code', 'test_settings.manage')->value('id'));

        $this->actingAs($counselor->fresh())->get(route('admin.test-settings.edit'))->assertOk();
    }

    public function test_disabled_or_unknown_role_cannot_enter_admin(): void
    {
        Role::where('code', 'STAFF')->update(['is_active' => false]);

        $this->assertFalse($this->user('STAFF')->canAccessAdmin());
        $this->assertFalse($this->user('KHONG_CO')->canAccessAdmin());
        $this->assertFalse(User::factory()->inactive()->create()->canAccessAdmin(), 'Tài khoản bị khóa không vào được dù là quản trị viên');

        $this->actingAs($this->user('STAFF'))->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }
}
