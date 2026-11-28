<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PostFeaturedImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_replacing_featured_image_of_existing_post_saves(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');

        $category = Category::create(['name' => 'Tin tức', 'slug' => 'tin-tuc']);
        Storage::disk('public')->put('posts/old.jpg', UploadedFile::fake()->image('old.jpg')->getContent());

        $post = Post::create([
            'title' => 'Bài cũ',
            'slug' => 'bai-cu',
            'content' => '<p>Nội dung</p>',
            'status' => PostStatus::Published,
            'category_id' => $category->id,
            'featured_image' => 'posts/old.jpg',
        ]);

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'featured_image' => [UploadedFile::fake()->image('new.jpg', 1200, 800)],
                'featured_image_fit' => 'contain',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();
        $this->assertSame('contain', $post->featured_image_fit);
        $this->assertNotSame('posts/old.jpg', $post->featured_image);
    }
}
