<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Models\Category;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');
    }

    public function test_choosing_a_menu_group_shows_new_category_in_header_and_page_works(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm(['name' => 'Học bổng', 'slug' => 'hoc-bong'])
            ->fillForm(['menu_group' => 'giao-duc-tai-duc'])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::where('slug', 'hoc-bong')->firstOrFail();

        $this->assertTrue($category->is_in_menu);
        $this->assertSame(url('/giao-duc-tai-duc/hoc-bong'), $category->menuUrl());

        $this->get('/')->assertOk()->assertSee('href="'.url('/giao-duc-tai-duc/hoc-bong').'"', false);

        $this->get('/giao-duc-tai-duc/hoc-bong')->assertOk()->assertSee('HỌC BỔNG');
    }

    public function test_new_category_goes_to_end_of_its_group(): void
    {
        Category::create(['name' => 'A', 'slug' => 'a', 'menu_group' => 'tu-van', 'is_in_menu' => true, 'sort_order' => 7]);

        $category = Category::create(['name' => 'B', 'slug' => 'b', 'menu_group' => 'tu-van', 'is_in_menu' => true]);

        $this->assertSame(8, $category->sort_order);
    }

    public function test_hidden_category_is_not_in_header_but_page_still_works(): void
    {
        Category::create(['name' => 'Ẩn đi', 'slug' => 'an-di', 'menu_group' => 'blog', 'is_in_menu' => false]);

        $this->get('/')->assertOk()->assertDontSee('Ẩn đi');
        $this->get('/blog/an-di')->assertOk();
    }

    public function test_custom_url_is_used_in_header(): void
    {
        Category::create(['name' => 'Link ngoài', 'slug' => 'link-ngoai', 'menu_group' => 'tu-van', 'is_in_menu' => true, 'url' => 'https://example.com/x']);

        $this->get('/')->assertSee('href="https://example.com/x"', false);
    }

    public function test_category_page_requires_matching_group(): void
    {
        Category::create(['name' => 'Học bổng', 'slug' => 'hoc-bong', 'menu_group' => 'giao-duc-tai-duc', 'is_in_menu' => true]);

        $this->get('/tu-van/hoc-bong')->assertNotFound();
        $this->get('/giao-duc-tai-duc/khong-ton-tai')->assertNotFound();
    }

    public function test_existing_fixed_routes_still_win(): void
    {
        $this->get('/tu-van/quy-trinh-tu-van')->assertOk();
    }

    public function test_main_menu_child_shows_in_parent_dropdown(): void
    {
        $parent = Category::create(['name' => 'Dịch vụ', 'slug' => 'dich-vu', 'menu_group' => 'main', 'is_in_menu' => true, 'url' => '/dich-vu']);

        Livewire::test(CreateCategory::class)
            ->fillForm(['name' => 'Luyện thi', 'slug' => 'luyen-thi'])
            ->fillForm(['menu_group' => 'main'])
            ->fillForm(['parent_id' => $parent->id, 'url' => '/luyen-thi'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/')->assertSee('Dịch vụ')->assertSee('href="'.url('/luyen-thi').'"', false);
    }

    public function test_switching_group_away_from_main_clears_parent(): void
    {
        $parent = Category::create(['name' => 'Dịch vụ', 'slug' => 'dich-vu', 'menu_group' => 'main', 'is_in_menu' => true]);
        $child = Category::create(['name' => 'Con', 'slug' => 'con', 'menu_group' => 'main', 'parent_id' => $parent->id, 'is_in_menu' => true]);

        Livewire::test(EditCategory::class, ['record' => $child->getKey()])
            ->fillForm(['menu_group' => 'blog'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($child->fresh()->parent_id);
        $this->get('/blog/con')->assertOk();
    }
}
