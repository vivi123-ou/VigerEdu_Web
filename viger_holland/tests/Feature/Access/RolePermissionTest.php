<?php

namespace Tests\Feature\Access;

use App\Models\User;
use App\Modules\Access\Models\Permission;
use App\Modules\Access\Models\Role;
use App\Modules\Access\Permissions;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phân quyền nằm trong bảng roles / permissions: Quản trị viên > Tư vấn viên > Nhân viên (Nhân viên gồm cả marketing).
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

        $this->assertSame(['ADMIN', 'COUNSELOR', 'STAFF'], Role::orderBy('sort_order')->pluck('code')->all(), 'Không còn vai trò Marketing riêng');
        $this->assertCount(12, $permissions('ADMIN'));
        $this->assertSame(['attempts.view', 'dashboard.view'], $permissions('COUNSELOR'));
        $this->assertSame(['content.manage', 'dashboard.view'], $permissions('STAFF'), 'Nhân viên gồm cả việc marketing: sửa nội dung trang');
    }

    public function test_only_admin_can_export_data_even_if_the_permission_is_ticked_by_mistake(): void
    {
        $this->assertTrue($this->user('ADMIN')->canExportData());

        Role::where('code', 'COUNSELOR')->first()->permissions()->attach(Permission::where('code', Permissions::EXPORT)->value('id'));

        foreach (['COUNSELOR', 'STAFF'] as $role) {
            $this->assertFalse($this->user($role)->canExportData(), "{$role} không được xuất dữ liệu");
        }
    }

    public function test_each_role_reaches_only_its_pages(): void
    {
        $this->actingAs($this->user('COUNSELOR'));
        $this->get(route('filament.admin.pages.dashboard'))->assertOk()
            ->assertDontSee(route('filament.admin.pages.test-settings'), false)
            ->assertDontSee(route('admin.media.index'), false);
        $this->get(route('filament.admin.pages.test-settings'))->assertForbidden();
        $this->get(route('filament.admin.resources.questions.index'))->assertForbidden();
        $this->get(route('admin.media.index'))->assertForbidden();

        $this->actingAs($this->user('STAFF'));
        $this->get(route('filament.admin.pages.dashboard'))->assertOk()->assertSee(route('admin.media.index'), false);
        $this->get(route('admin.media.index'))->assertOk();
        $this->get(route('filament.admin.pages.test-settings'))->assertForbidden();
        $this->get(route('filament.admin.resources.users.index'))->assertForbidden();
        $this->get(route('filament.admin.resources.roles.index'))->assertForbidden();
    }

    public function test_admin_can_grant_a_permission_to_a_role_without_code_changes(): void
    {
        $counselor = $this->user('COUNSELOR');
        $this->actingAs($counselor)->get(route('filament.admin.pages.test-settings'))->assertForbidden();

        Role::where('code', 'COUNSELOR')->first()->permissions()->attach(Permission::where('code', 'test_settings.manage')->value('id'));

        $this->actingAs($counselor->fresh())->get(route('filament.admin.pages.test-settings'))->assertOk();
    }

    public function test_disabled_or_unknown_role_cannot_enter_admin(): void
    {
        Role::where('code', 'STAFF')->update(['is_active' => false]);

        $this->assertFalse($this->user('STAFF')->canAccessAdmin());
        $this->assertFalse($this->user('KHONG_CO')->canAccessAdmin());
        $this->assertFalse(User::factory()->inactive()->create()->canAccessAdmin(), 'Tài khoản bị khóa không vào được dù là quản trị viên');

        $this->actingAs($this->user('STAFF'))->get(route('filament.admin.pages.dashboard'))->assertRedirect(route('filament.admin.auth.login'));
        $this->assertGuest();
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }
}
