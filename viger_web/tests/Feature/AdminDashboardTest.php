<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Widgets\LatestContactMessages;
use App\Filament\Widgets\LatestPosts;
use App\Filament\Widgets\PostsByCategoryChart;
use App\Filament\Widgets\PostsPerMonthChart;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\WelcomeBanner;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');

        $category = Category::create(['name' => 'Du học Đức', 'slug' => 'du-hoc-duc']);

        Post::create([
            'title' => 'Bài viết thử nghiệm',
            'slug' => 'bai-viet-thu-nghiem',
            'content' => '<p>Nội dung</p>',
            'status' => PostStatus::Published,
            'category_id' => $category->id,
        ]);

        ContactMessage::create(['name' => 'Nguyễn Văn A', 'email' => 'a@example.com', 'phone' => '0900000000']);
    }

    public function test_dashboard_page_renders(): void
    {
        $this->get('/admin')->assertOk();
    }

    public function test_welcome_banner_renders(): void
    {
        Livewire::test(WelcomeBanner::class)
            ->assertOk()
            ->assertSee('Viết bài mới');
    }

    public function test_stats_overview_renders(): void
    {
        Livewire::test(StatsOverview::class)
            ->assertOk()
            ->assertSee('Bài viết đã xuất bản')
            ->assertSee('Liên hệ mới (7 ngày)');
    }

    public function test_charts_render(): void
    {
        Livewire::test(PostsPerMonthChart::class)->assertOk();
        Livewire::test(PostsByCategoryChart::class)->assertOk();
    }

    public function test_latest_tables_list_records(): void
    {
        Livewire::test(LatestPosts::class)
            ->assertOk()
            ->assertSee('Bài viết thử nghiệm');

        Livewire::test(LatestContactMessages::class)
            ->assertOk()
            ->assertSee('Nguyễn Văn A');
    }
}
