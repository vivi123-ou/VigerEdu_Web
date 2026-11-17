<?php

namespace Tests\Feature\Access;

use App\Models\User;
use App\Modules\Access\Filament\Resources\Roles\Pages\CreateRole;
use App\Modules\Access\Filament\Resources\Roles\Pages\EditRole;
use App\Modules\Access\Filament\Resources\Users\Pages\CreateUser;
use App\Modules\Access\Filament\Resources\Users\Pages\EditUser;
use App\Modules\Access\Models\Permission;
use App\Modules\Access\Models\Role;
use App\Modules\Access\Permissions;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Trang "Tài khoản" và "Vai trò & quyền" (Filament): quản trị viên tạo tài khoản tư vấn viên / nhân viên,
 * thêm vai trò mới và tick quyền; chặn các cách tự nâng quyền hoặc tự khóa mình.
 */
class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SettingSeeder::class, RoleSeeder::class]);
        $this->admin = User::factory()->create(['username' => 'SPN']);
        $this->actingAs($this->admin);
    }

    public function test_admin_creates_a_counselor_account_that_can_log_in(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Chị Lan', 'username' => 'lan.tuvan', 'email' => 'lan@example.com', 'role' => User::ROLE_COUNSELOR,
                'is_active' => true, 'password' => 'MatKhau2026', 'password_confirmation' => 'MatKhau2026',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('username', 'lan.tuvan')->firstOrFail();
        $this->assertSame(User::ROLE_COUNSELOR, $user->role);
        $this->assertTrue(Hash::check('MatKhau2026', $user->password), 'Mật khẩu được băm, không lưu chữ thường');
        $this->assertTrue($user->canAccessAdmin());
    }

    public function test_weak_or_mismatched_password_and_duplicate_username_are_rejected(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'A', 'username' => 'SPN', 'role' => User::ROLE_STAFF, 'password' => 'ngan', 'password_confirmation' => 'khac'])
            ->call('create')
            ->assertHasFormErrors(['username' => 'unique', 'password'])
            ->assertSee('Tên đăng nhập này đã có người dùng.')
            ->assertSee('phải dài ít nhất 10 ký tự')
            ->assertDontSee('validation.');
    }

    public function test_editing_without_password_keeps_the_old_one(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'password' => 'MatKhauCu123']);

        Livewire::test(EditUser::class, ['record' => $staff->getRouteKey()])
            ->fillForm(['name' => 'Tên mới', 'password' => '', 'password_confirmation' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Tên mới', $staff->fresh()->name);
        $this->assertTrue(Hash::check('MatKhauCu123', $staff->fresh()->password));
    }

    public function test_admin_cannot_lock_or_demote_themself(): void
    {
        Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
            ->assertFormFieldIsDisabled('role')
            ->assertFormFieldIsDisabled('is_active')
            ->fillForm(['role' => User::ROLE_STAFF, 'is_active' => false])
            ->call('save');

        $this->assertSame(User::ROLE_ADMIN, $this->admin->fresh()->role);
        $this->assertTrue($this->admin->fresh()->is_active);
        $this->assertFalse($this->admin->can('delete', $this->admin));
    }

    public function test_non_admin_account_manager_cannot_touch_admins_or_create_admins(): void
    {
        $role = Role::create(['code' => 'TRUONG_NHOM', 'name' => 'Trưởng nhóm', 'is_active' => true, 'sort_order' => 9]);
        $role->permissions()->attach(Permission::whereIn('code', ['dashboard.view', 'accounts.manage'])->pluck('id'));
        $manager = User::factory()->create(['role' => 'TRUONG_NHOM']);
        $this->actingAs($manager);

        $this->get(route('filament.admin.resources.users.index'))->assertOk();
        $this->get(route('filament.admin.resources.users.edit', $this->admin))->assertForbidden();

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'X', 'username' => 'leoquyen', 'role' => User::ROLE_ADMIN, 'password' => 'MatKhau2026', 'password_confirmation' => 'MatKhau2026'])
            ->call('create')
            ->assertHasFormErrors(['role']);

        $this->assertDatabaseMissing('users', ['username' => 'leoquyen']);
    }

    public function test_admin_adds_a_role_and_ticks_permissions_but_cannot_grant_export(): void
    {
        Livewire::test(CreateRole::class)
            ->assertFormFieldExists('permissions')
            ->fillForm([
                'name' => 'Kế toán', 'code' => 'KE_TOAN', 'is_active' => true,
                'permissions' => Permission::whereIn('code', ['dashboard.view', 'attempts.view'])->pluck('id')->all(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $role = Role::where('code', 'KE_TOAN')->firstOrFail();
        $this->assertFalse($role->is_system, 'Vai trò thêm mới xóa được');
        $this->assertSame(['attempts.view', 'dashboard.view'], $role->permissions->pluck('code')->sort()->values()->all());

        Livewire::test(CreateRole::class)
            ->fillForm(['name' => 'Xuất', 'code' => 'XUAT', 'permissions' => [Permission::where('code', Permissions::EXPORT)->value('id')]])
            ->call('create');
        $this->assertFalse(Role::where('code', 'XUAT')->first()?->permissions()->where('code', Permissions::EXPORT)->exists() ?? false);
    }

    public function test_admin_role_is_locked_and_core_or_used_roles_cannot_be_deleted(): void
    {
        $adminRole = Role::where('code', User::ROLE_ADMIN)->first();
        $staffRole = Role::where('code', User::ROLE_STAFF)->first();
        $extra = Role::create(['code' => 'TAM', 'name' => 'Tạm', 'is_active' => true, 'sort_order' => 9]);

        $this->get(route('filament.admin.resources.roles.edit', $adminRole))->assertForbidden();
        $this->assertFalse($this->admin->can('delete', $staffRole), 'Vai trò lõi không xóa được');
        $this->assertTrue($this->admin->can('delete', $extra));

        User::factory()->create(['role' => 'TAM']);
        $this->assertFalse($this->admin->can('delete', $extra), 'Vai trò còn tài khoản thì không xóa được');

        Livewire::test(EditRole::class, ['record' => $staffRole->getRouteKey()])
            ->assertFormFieldIsDisabled('code');
    }
}
