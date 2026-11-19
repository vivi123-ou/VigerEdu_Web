<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Dashboard;
use App\Models\User;
use App\Modules\Access\Filament\Resources\Users\Pages\ListUsers;
use App\Modules\Assessment\Filament\Pages\TestSettings;
use App\Modules\Assessment\Filament\Resources\Questions\Pages\ListQuestions;
use App\Modules\Content\Filament\Pages\IntroPage;
use App\Modules\Content\Filament\Pages\MediaLibrary;
use App\Support\AdminMenu;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Khung khu quản trị (F2): menu đặt tên theo việc làm, nhóm đúng thứ tự, nhãn "Sắp có", ô lối tắt, nút "?" hướng dẫn
 * trên mọi trang, thanh trên có tên và vai trò; mọi mục menu là trang Filament (không còn giao diện cũ).
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

    public function test_home_shows_menu_groups_shortcuts_stats_and_who_is_logged_in(): void
    {
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Trang chủ quản trị')
            ->assertSee('Xin chào Thầy Phương')
            ->assertSee('Quản trị viên')
            ->assertSeeInOrder(['Khách hàng', 'Bài trắc nghiệm', 'Trang khách xem', 'Quản lý hệ thống'])
            ->assertSee('Câu hỏi trắc nghiệm')
            ->assertSee('Cách bốc đề &amp; chấm điểm', false)
            ->assertSee('Ô giới thiệu trên viger.edu.vn')
            ->assertSee('Tài khoản nhân sự')
            ->assertSee('Phân quyền')
            ->assertDontSee('Sắp có')
            ->assertSee('Xem trang làm bài như khách')
            ->assertSee('Câu hỏi đang dùng');
    }

    public function test_shortcuts_put_ready_tasks_first_and_follow_permissions(): void
    {
        $admin = auth()->user();
        $shortcuts = AdminMenu::shortcutsFor($admin);

        $this->assertCount(6, $shortcuts);
        $this->assertSame(['lookup', 'contacts', 'stats', 'questions', 'settings', 'intro'], $shortcuts->pluck('key')->all(), 'Việc dùng được ngay đứng trước mục Sắp có');

        $this->assertSame(['lookup'], AdminMenu::shortcutsFor(User::factory()->create(['role' => User::ROLE_COUNSELOR]))->pluck('key')->all());
        $this->assertSame(['contacts', 'intro', 'media'], AdminMenu::shortcutsFor(User::factory()->create(['role' => User::ROLE_STAFF]))->pluck('key')->all());
    }

    public function test_every_menu_item_opens_for_admin(): void
    {
        foreach (AdminMenu::items() as $item) {
            $this->get(AdminMenu::url($item))->assertOk();
        }
    }

    public function test_coming_soon_page_explains_the_stage_and_unknown_items_are_not_found(): void
    {
        // H7 xong: không còn mục "Sắp có"; mục đã làm mở trang "Sắp có" thì báo không tìm thấy.
        $this->assertSame([], AdminMenu::items()->filter(fn (array $item) => AdminMenu::isComingSoon($item))->keys()->all());
        $this->get(route('filament.admin.pages.sap-co', ['muc' => 'seo']))->assertNotFound();
        $this->get(route('filament.admin.pages.sap-co', ['muc' => 'customers']))->assertNotFound();

        $this->get(route('filament.admin.pages.sap-co', ['muc' => 'khong-co']))->assertNotFound();
        $this->get(route('filament.admin.pages.sap-co', ['muc' => 'questions']))->assertNotFound();
    }

    public function test_every_filament_page_has_the_help_button_with_steps(): void
    {
        Livewire::test(Dashboard::class)->assertActionExists('help')->mountAction('help')->assertMountedActionModalSee('Trang này dùng để làm gì?');
        Livewire::test(ListQuestions::class)->assertActionExists('help')->mountAction('help')->assertMountedActionModalSee('Thấy câu chưa hay');
        Livewire::test(TestSettings::class)->assertActionExists('help')->mountAction('help')->assertMountedActionModalSee('Lưu nhầm');
        Livewire::test(ListUsers::class)->assertActionExists('help')->mountAction('help')->assertMountedActionModalSee('Người nghỉ việc');
    }

    public function test_content_pages_are_filament_pages_with_help_and_no_old_layout_left(): void
    {
        Livewire::test(IntroPage::class)->assertActionExists('help')->mountAction('help')->assertMountedActionModalSee('Sửa ngay trên trang');
        Livewire::test(MediaLibrary::class)->assertActionExists('help')->mountAction('help')->assertMountedActionModalSee('Lấy lại ảnh đã xóa');

        $this->get(MediaLibrary::getUrl())->assertOk()->assertSee('Kho ảnh')
            ->assertSee(route('filament.admin.pages.dashboard'), false)
            ->assertDontSee('css/admin.css', false);

        $this->assertFalse(view()->exists('layouts.admin'), 'Không còn giao diện quản trị cũ');
        AdminMenu::items()->each(fn (array $item) => $this->assertArrayHasKey('page', $item, "Mục {$item['key']} là trang Filament"));
    }

    public function test_unknown_admin_page_returns_404(): void
    {
        $this->get('/admin/khong-ton-tai')->assertNotFound();
    }
}
