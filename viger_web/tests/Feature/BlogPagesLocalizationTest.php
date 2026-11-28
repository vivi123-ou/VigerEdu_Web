<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BlogPagesLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_page_shows_vietnamese_text_by_default(): void
    {
        $this->get('/blog')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Blog - Kiến thức du học Đức', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('KIẾN THỨC DU HỌC ĐỨC')
            ->assertSee('Tìm kiếm bài viết, tài liệu...')
            ->assertSee('Bài viết mới nhất về du học tại Đức')
            ->assertDontSee('STUDY IN GERMANY INSIGHTS');
    }

    public function test_blog_page_shows_english_text_in_english_mode(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/en/blog')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Blog - Study in Germany Insights', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('STUDY IN GERMANY INSIGHTS')
            ->assertSee('Search articles, resources...')
            ->assertSee('Latest articles about studying in Germany')
            ->assertSee('Begin your journey toward the future')
            ->assertDontSee('KIẾN THỨC DU HỌC ĐỨC')
            ->assertDontSee('Tìm kiếm bài viết, tài liệu...')
            ->assertDontSee('Khởi đầu hành trình chinh phục tương lai');
    }

    public function test_blog_category_pages_show_vietnamese_text_by_default(): void
    {
        $this->get('/blog/hieu-ve-duc')
            ->assertOk()
            ->assertSee('HIỂU VỀ NƯỚC ĐỨC')
            ->assertSee('Bài Đăng Mới Nhất');

        $this->get('/blog/cuoc-song-o-duc')
            ->assertOk()
            ->assertSee('CUỘC SỐNG Ở ĐỨC')
            ->assertSee('Chuyên đề &amp; Bài viết', false);
    }

    public function test_blog_category_pages_show_english_text_in_english_mode(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/en/blog/understanding-germany')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Understanding Germany', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('UNDERSTANDING GERMANY')
            ->assertSee('Culture &amp; Society', false)
            ->assertSee('Latest Posts')
            ->assertSee('There are no articles in this category yet.')
            ->assertDontSee('HIỂU VỀ NƯỚC ĐỨC')
            ->assertDontSee('Bài Đăng Mới Nhất');

        $this->withSession(['locale' => 'en'])
            ->get('/en/blog/life-in-germany')
            ->assertOk()
            ->assertSee('LIFE IN GERMANY')
            ->assertSee('Topics &amp; Articles', false)
            ->assertDontSee('CUỘC SỐNG Ở ĐỨC');
    }

    public function test_post_card_date_and_link_are_localized(): void
    {
        $category = Category::where('slug', 'hieu-ve-duc')->first()
            ?? Category::create(['name' => 'Hiểu về Đức', 'slug' => 'hieu-ve-duc', 'menu_group' => 'blog']);

        $this->travelTo(Carbon::create(2026, 11, 5));
        Post::create([
            'title' => ['vi' => 'Bài viết thử', 'en' => 'Test post'],
            'slug' => 'bai-viet-thu',
            'content' => ['vi' => '<p>Nội dung</p>', 'en' => '<p>Content</p>'],
            'status' => PostStatus::Published,
            'category_id' => $category->id,
        ]);
        $this->travelBack();

        $this->get('/blog/hieu-ve-duc')
            ->assertSee('05 tháng 11, 2026')
            ->assertSee('Xem chi tiết');

        $this->withSession(['locale' => 'en'])
            ->get('/en/blog/understanding-germany')
            ->assertSee('Nov 05, 2026')
            ->assertSee('View details')
            ->assertDontSee('Xem chi tiết');
    }

    public function test_admin_category_without_english_description_uses_english_default(): void
    {
        Category::create([
            'name' => ['vi' => 'Học bổng', 'en' => 'Scholarships'],
            'description' => ['vi' => 'Mô tả tiếng Việt'],
            'slug' => 'hoc-bong',
            'menu_group' => 'blog',
        ]);

        $this->withSession(['locale' => 'en'])
            ->get('/en/blog/scholarships')
            ->assertOk()
            ->assertSee('A collection of articles in the Scholarships category.')
            ->assertDontSee('Mô tả tiếng Việt');
    }
}
