<?php

namespace Tests\Feature;

use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Models\Banner;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BannerUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');
    }

    public function test_upload_on_create_is_saved(): void
    {
        Livewire::test(CreateBanner::class)
            ->fillForm(['title.vi' => 'Up', 'image_source' => 'upload', 'image' => UploadedFile::fake()->image('a.jpg', 1920, 1080)])
            ->call('create')
            ->assertHasNoFormErrors();

        $banner = Banner::where('title->vi', 'Up')->firstOrFail();
        $this->assertStringStartsWith('banners/', (string) $banner->image);
        Storage::disk('public')->assertExists($banner->image);
    }

    public function test_upload_on_edit_replaces_external_url(): void
    {
        $banner = Banner::create(['title' => ['vi' => 'Cũ'], 'image' => 'https://example.com/x.jpg', 'sort_order' => 1]);

        Livewire::test(EditBanner::class, ['record' => $banner->getKey()])
            ->fillForm(['image_source' => 'upload'])
            ->fillForm(['image' => UploadedFile::fake()->image('b.jpg', 1920, 1080)])
            ->call('save')
            ->assertHasNoFormErrors();

        $image = (string) $banner->fresh()->image;
        $this->assertStringStartsWith('banners/', $image);
        Storage::disk('public')->assertExists($image);
    }

    public function test_editing_uploaded_banner_keeps_image(): void
    {
        Storage::disk('public')->put('banners/c.jpg', 'x');
        $banner = Banner::create(['title' => ['vi' => 'Có ảnh'], 'image' => 'banners/c.jpg', 'sort_order' => 1]);

        Livewire::test(EditBanner::class, ['record' => $banner->getKey()])
            ->fillForm(['title.vi' => 'Sửa'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('banners/c.jpg', $banner->fresh()->image);
    }
}
