<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Pages\ManagePostDetailPage;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');

        $this->category = Category::create(['name' => 'Tin tức', 'slug' => 'tin-tuc']);
    }

    private function makePost(string $slug): Post
    {
        return Post::create([
            'title' => 'Bài '.$slug,
            'slug' => $slug,
            'content' => '<p>Nội dung</p>',
            'status' => PostStatus::Published,
            'category_id' => $this->category->id,
        ]);
    }

    public function test_defaults_render_like_before(): void
    {
        $this->makePost('chinh');
        $this->makePost('lien-quan');

        $this->get('/bai-viet/chinh')
            ->assertOk()
            ->assertSee('VIGER.EDU.VN')
            ->assertSee('Bài Viết Liên Quan')
            ->assertSee('Cần Tư Vấn Du Học Đức?')
            ->assertSee('Bài lien-quan');
    }

    public function test_admin_changes_show_on_post_page(): void
    {
        $this->makePost('chinh');
        foreach (range(1, 4) as $i) {
            $this->makePost("lien-quan-$i");
        }

        Livewire::test(ManagePostDetailPage::class)
            ->assertOk()
            ->fillForm([
                'post_detail_author_name' => 'Ban biên tập',
                'post_detail_related_title' => 'Đọc thêm',
                'post_detail_related_count' => 2,
                'post_detail_cta_title' => 'Gọi ngay cho chúng tôi',
                'post_detail_cta_button_url' => '/tu-van',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $html = $this->get('/bai-viet/chinh')
            ->assertOk()
            ->assertSee('Ban biên tập')
            ->assertSee('Đọc thêm')
            ->assertSee('Gọi ngay cho chúng tôi')
            ->assertSee('href="/tu-van"', false)
            ->getContent();

        $this->assertSame(2, substr_count($html, 'class="related-post-item"'));
    }

    public function test_blocks_can_be_hidden_and_keep_their_text(): void
    {
        $this->makePost('chinh');
        $this->makePost('lien-quan');

        Livewire::test(ManagePostDetailPage::class)
            ->fillForm(['post_detail_cta_title' => 'Tiêu đề giữ lại'])
            ->call('save')
            ->fillForm([
                'post_detail_show_author_box' => false,
                'post_detail_show_related' => false,
                'post_detail_show_cta' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/bai-viet/chinh')
            ->assertOk()
            ->assertDontSee('class="author-box"', false)
            ->assertDontSee('class="related-post-item"', false)
            ->assertDontSee('Tiêu đề giữ lại');

        // Bật lại: nội dung cũ vẫn còn
        Livewire::test(ManagePostDetailPage::class)
            ->fillForm(['post_detail_show_cta' => true])
            ->call('save');

        $this->get('/bai-viet/chinh')->assertSee('Tiêu đề giữ lại');
    }
}
