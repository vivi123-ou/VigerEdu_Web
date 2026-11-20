<?php

namespace Tests\Feature;

use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Models\Banner;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BannerFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');
    }

    public function test_can_create_banner_with_sort_order_zero_and_image_url(): void
    {
        Livewire::test(CreateBanner::class)
            ->fillForm([
                'title.vi' => 'Banner số 0',
                'image_source' => 'url',
                'image_url' => 'https://example.com/anh.jpg',
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $banner = Banner::where('title->vi', 'Banner số 0')->firstOrFail();
        $this->assertSame(0, $banner->sort_order);
        $this->assertSame('https://example.com/anh.jpg', $banner->image);
    }

    public function test_negative_sort_order_is_rejected(): void
    {
        Livewire::test(CreateBanner::class)
            ->fillForm([
                'title.vi' => 'Âm',
                'image_source' => 'url',
                'image_url' => 'https://example.com/anh.jpg',
                'sort_order' => -1,
            ])
            ->call('create')
            ->assertHasFormErrors(['sort_order']);
    }

    public function test_editing_banner_with_external_image_keeps_it_without_reupload(): void
    {
        $banner = Banner::create([
            'title' => ['vi' => 'Cũ'],
            'image' => 'https://images.unsplash.com/photo-1?w=1920',
            'sort_order' => 1,
        ]);

        Livewire::test(EditBanner::class, ['record' => $banner->getKey()])
            ->assertSchemaStateSet([
                'image_source' => 'url',
                'image_url' => 'https://images.unsplash.com/photo-1?w=1920',
            ])
            ->fillForm(['title.vi' => 'Đã sửa'])
            ->call('save')
            ->assertHasNoFormErrors();

        $banner->refresh();
        $this->assertSame('Đã sửa', $banner->getTranslation('title', 'vi'));
        $this->assertSame('https://images.unsplash.com/photo-1?w=1920', $banner->image);
    }

    public function test_url_mode_requires_a_valid_link(): void
    {
        Livewire::test(CreateBanner::class)
            ->fillForm([
                'title.vi' => 'Thiếu ảnh',
                'image_source' => 'url',
                'image_url' => 'khong-phai-link',
            ])
            ->call('create')
            ->assertHasFormErrors(['image_url']);
    }

    public function test_upload_mode_requires_an_image(): void
    {
        Livewire::test(CreateBanner::class)
            ->fillForm(['title.vi' => 'Thiếu ảnh', 'image_source' => 'upload'])
            ->call('create')
            ->assertHasFormErrors(['image']);
    }
}
