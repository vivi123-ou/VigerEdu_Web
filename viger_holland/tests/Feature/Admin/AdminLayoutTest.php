<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\AdminMenu;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create(['name' => 'Thầy Phương']));
    }

    public function test_dashboard_shows_menu_groups_and_question_counts(): void
    {
        $response = $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Xin chào Thầy Phương')
            ->assertSee('Xem trang test như khách')
            ->assertSeeInOrder(['Bảng điều khiển', 'Bài test', 'Nội dung trang', 'Dữ liệu', 'Cấu hình'])
            ->assertSee('THPT – 54 câu');

        foreach (AdminMenu::items() as $item) {
            $response->assertSee($item['label']);
        }

        $this->assertSame(54, $response->viewData('partBCount'));
        $this->assertSame(21, $response->viewData('partACount'));
    }

    public function test_every_pending_menu_item_opens_a_coming_soon_page_with_help(): void
    {
        foreach (AdminMenu::items()->whereNotNull('stage') as $item) {
            $this->get(route('admin.section', $item['key']))
                ->assertOk()
                ->assertSee('Mục này đang được xây dựng')
                ->assertSee('giai đoạn '.$item['stage'])
                ->assertSee('Hướng dẫn trang này', false);
        }
    }

    public function test_unknown_admin_section_returns_404(): void
    {
        $this->get('/admin/khong-ton-tai')->assertNotFound();
    }
}
