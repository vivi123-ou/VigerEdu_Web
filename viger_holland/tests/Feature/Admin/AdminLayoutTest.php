<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Dashboard;
use App\Models\User;
use App\Modules\Access\Filament\Resources\Users\Pages\ListUsers;
use App\Modules\Assessment\Filament\Pages\TestSettings;
use App\Modules\Assessment\Filament\Resources\Questions\Pages\ListQuestions;
use App\Support\AdminMenu;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Khung khu quản trị (F2): menu đặt tên theo việc làm, nhóm đúng thứ tự, nhãn "Sắp có", ô lối tắt, nút "?" hướng dẫn
 * trên mọi trang, thanh trên có tên và vai trò; các trang cũ vẫn mở được và có đường về trang quản trị.
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
            ->assertSee('Sắp có')
            ->assertSee('Xem trang làm bài như khách')
            ->assertSee('Câu hỏi đang dùng');
    }

    public function test_shortcuts_put_ready_tasks_first_and_follow_permissions(): void
    {
        $admin = auth()->user();
        $shortcuts = AdminMenu::shortcutsFor($admin);

        $this->assertCount(6, $shortcuts);
        $this->assertSame(['questions', 'settings', 'intro', 'media', 'appearance', 'users'], $shortcuts->pluck('key')->all(), 'Việc dùng được ngay đứng trước mục Sắp có');

        $this->assertSame(['lookup'], AdminMenu::shortcutsFor(User::factory()->create(['role' => User::ROLE_COUNSELOR]))->pluck('key')->all());
        $this->assertSame(['intro', 'media', 'contacts'], AdminMenu::shortcutsFor(User::factory()->create(['role' => User::ROLE_STAFF]))->pluck('key')->all());
    }

    public function test_every_menu_item_opens_for_admin(): void
    {
        foreach (AdminMenu::items() as $item) {
            $this->get(AdminMenu::url($item))->assertOk();
        }
    }

    public function test_coming_soon_page_explains_the_stage_and_unknown_items_are_not_found(): void
    {
        $this->get(route('filament.admin.pages.sap-co', ['muc' => 'customers']))
            ->assertOk()
            ->assertSee('Danh sách khách đã làm bài')
            ->assertSee('dự kiến xong ở giai đoạn H6');

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

    public function test_old_content_pages_still_open_with_link_back_and_plain_names(): void
    {
        $this->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('Kho ảnh')
            ->assertSee('Về trang quản trị')
            ->assertSee(route('filament.admin.pages.dashboard'), false)
            ->assertSee(route('filament.admin.auth.logout'), false);
    }

    public function test_embed_code_is_tucked_away_for_admin_and_hidden_for_staff(): void
    {
        $this->get(route('admin.content.edit', 'embed'))->assertOk()
            ->assertSee('Dành cho người làm web chính')->assertSee('embed.js', false);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->get(route('admin.content.edit', 'embed'))->assertOk()
            ->assertDontSee('Dành cho người làm web chính')->assertDontSee('embed.js', false);
    }

    public function test_unknown_admin_page_returns_404(): void
    {
        $this->get('/admin/khong-ton-tai')->assertNotFound();
    }
}
