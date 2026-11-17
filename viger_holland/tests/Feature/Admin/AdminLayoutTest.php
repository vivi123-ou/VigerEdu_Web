<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Khung khu quản trị Filament: bảng điều khiển, menu theo nhóm, chữ "Trang này làm gì?", noindex,
 * các trang nội dung cũ vẫn mở được và có đường về trang quản trị.
 */
class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SettingSeeder::class, RoleSeeder::class, TestConfigSeeder::class]);
        $this->actingAs(User::factory()->create(['name' => 'Thầy Phương']));
    }

    public function test_dashboard_shows_menu_groups_stats_and_help(): void
    {
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Bảng điều khiển')
            ->assertSee('Xem nhanh tình trạng thư viện câu hỏi')
            ->assertSeeInOrder(['Bài trắc nghiệm', 'Nội dung trang', 'Hệ thống'])
            ->assertSee('Thư viện câu hỏi')
            ->assertSee('Cài đặt bài test')
            ->assertSee('Trang giới thiệu')
            ->assertSee('Tài khoản')
            ->assertSee('Vai trò &amp; quyền', false)
            ->assertSee('Câu hỏi đã duyệt');
    }

    public function test_every_admin_page_opens(): void
    {
        foreach (['filament.admin.resources.questions.index', 'filament.admin.resources.questions.create', 'filament.admin.pages.test-settings',
            'filament.admin.resources.users.index', 'filament.admin.resources.users.create', 'filament.admin.resources.roles.index'] as $route) {
            $this->get(route($route))->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    public function test_old_content_pages_still_open_with_link_back_to_dashboard(): void
    {
        $this->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('Về trang quản trị')
            ->assertSee(route('filament.admin.pages.dashboard'), false)
            ->assertSee(route('filament.admin.auth.logout'), false);
    }

    public function test_unknown_admin_page_returns_404(): void
    {
        $this->get('/admin/khong-ton-tai')->assertNotFound();
    }
}
