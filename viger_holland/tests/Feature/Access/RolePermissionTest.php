<?php

namespace Tests\Feature\Access;

use App\Models\User;
use App\Modules\Access\Models\Permission;
use App\Modules\Access\Models\Role;
use App\Modules\Access\Permissions;
use App\Modules\Content\Filament\Pages\MediaLibrary;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phân quyền theo việc thật (Vân chốt 17/11): tư vấn viên chỉ tra cứu từng khách; nhân viên chỉ liên lạc khách mới
 * và sửa trang khách xem; số liệu, danh sách toàn bộ khách, tải dữ liệu: quản trị viên. Trang không có quyền báo 404.
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

        $this->assertSame(['ADMIN', 'STAFF', 'COUNSELOR'], Role::orderBy('sort_order')->pluck('code')->all(), 'Thứ bậc: Quản trị viên > Nhân viên > Tư vấn viên');
        $this->assertCount(count(Permissions::ALL), $permissions('ADMIN'));
        $this->assertSame(['reports.lookup', 'schools.view'], $permissions('COUNSELOR'), 'Tư vấn viên tra cứu từng khách và xem danh sách trường');
        $this->assertSame(['contacts.view', 'content.manage'], $permissions('STAFF'), 'Nhân viên: liên lạc khách mới, sửa trang khách xem');
    }

    public function test_every_permission_reads_as_a_plain_sentence_with_a_group(): void
    {
        foreach (Permission::all() as $permission) {
            $this->assertStringStartsWith('Được ', $permission->name, $permission->code);
            $this->assertContains($permission->group, Permissions::groups(), $permission->code);
        }
    }

    public function test_only_admin_can_export_data_even_if_the_permission_is_ticked_by_mistake(): void
    {
        $this->assertTrue($this->user('ADMIN')->canExportData());

        Role::where('code', 'COUNSELOR')->first()->permissions()->attach(Permission::where('code', Permissions::EXPORT)->value('id'));

        foreach (['COUNSELOR', 'STAFF'] as $role) {
            $this->assertFalse($this->user($role)->canExportData(), "{$role} không được xuất dữ liệu");
        }
    }

    public function test_counselor_reaches_only_customer_lookup(): void
    {
        $this->actingAs($this->user('COUNSELOR'));

        $this->get(route('filament.admin.pages.dashboard'))->assertOk()
            ->assertSee('Tra cứu báo cáo khách')
            ->assertDontSee('Khách cần liên lạc')
            ->assertDontSee('Số liệu làm bài')
            ->assertDontSee('Câu hỏi đang dùng')
            ->assertDontSee('Câu hỏi trắc nghiệm')
            ->assertDontSee('Tài khoản nhân sự')
            ->assertDontSee('Phân quyền')
            ->assertDontSee(MediaLibrary::getUrl(), false);

        $this->get(route('filament.admin.pages.tra-cuu-bao-cao'))->assertOk();
        $this->get(route('filament.admin.pages.khach-can-lien-lac'))->assertNotFound();
        $this->get(route('filament.admin.pages.sap-co', ['muc' => 'stats']))->assertNotFound();
        $this->get(route('filament.admin.pages.test-settings'))->assertNotFound();
        $this->get(route('filament.admin.resources.questions.index'))->assertNotFound();
        $this->get(MediaLibrary::getUrl())->assertNotFound();
    }

    public function test_staff_reaches_contacts_and_customer_facing_pages_only(): void
    {
        $this->actingAs($this->user('STAFF'));

        $this->get(route('filament.admin.pages.dashboard'))->assertOk()
            ->assertSee('Khách cần liên lạc')
            ->assertSee(MediaLibrary::getUrl(), false)
            ->assertDontSee('Tra cứu báo cáo khách')
            ->assertDontSee('Số liệu làm bài')
            ->assertDontSee('Câu hỏi đang dùng')
            ->assertDontSee('Câu hỏi trắc nghiệm')
            ->assertDontSee('Tài khoản nhân sự')
            ->assertDontSee('Phân quyền')
            ->assertDontSee('Logo, màu, hotline');

        $this->get(MediaLibrary::getUrl())->assertOk();
        $this->get(route('filament.admin.pages.khach-can-lien-lac'))->assertOk();
        $this->get(route('filament.admin.pages.tra-cuu-bao-cao'))->assertNotFound();
        $this->get(route('filament.admin.pages.test-settings'))->assertNotFound();
        $this->get(route('filament.admin.resources.users.index'))->assertNotFound();
        $this->get(route('filament.admin.resources.roles.index'))->assertNotFound();
    }

    public function test_pages_of_limited_roles_do_not_mention_their_limits(): void
    {
        $this->actingAs($this->user('STAFF'));

        $this->get(route('filament.admin.pages.dashboard'))->assertOk()
            ->assertDontSee('không có quyền')->assertDontSee('chỉ quản trị viên')->assertDontSee('7 ngày');
    }

    public function test_admin_can_grant_a_permission_to_a_role_without_code_changes(): void
    {
        $counselor = $this->user('COUNSELOR');
        $this->actingAs($counselor)->get(route('filament.admin.pages.test-settings'))->assertNotFound();

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
