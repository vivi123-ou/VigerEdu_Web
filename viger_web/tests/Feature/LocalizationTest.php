<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => ['vi' => 'Tin tức', 'en' => 'News'],
            'slug' => 'tin-tuc',
        ]);
    }

    private function makePost(string $slug, array $title, array $extra = []): Post
    {
        return Post::create([
            'title' => $title,
            'slug' => $slug,
            'content' => ['vi' => '<p>Nội dung tiếng Việt</p>', 'en' => '<p>English content</p>'],
            'status' => PostStatus::Published,
            'category_id' => $this->category->id,
            ...$extra,
        ]);
    }

    public function test_site_defaults_to_vietnamese_with_language_toggle(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="vi">', false)
            ->assertSee('Về chúng tôi')
            ->assertSee('<span class="lang-active">VI</span>', false)
            ->assertSeeInOrder(['</nav>', 'class="lang-toggle"', 'href="'.url('/en').'"'], false)
            ->assertDontSee('lang-switcher', false);
    }

    public function test_switching_language_stores_locale_and_translates_interface(): void
    {
        $this->from('/blog')
            ->get('/lang/en')
            ->assertRedirect('/en/blog')
            ->assertSessionHas('locale', 'en');

        $this->get('/en')
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('<span id="nav-about">Study in Germany</span>', false)
            ->assertSee('<a href="'.url('/en/study-in-germany').'">Study in Germany</a>', false)
            ->assertDontSee('<span id="nav-about">Giáo dục tại Đức</span>', false)
            ->assertSee('<span class="lang-active">EN</span>', false)
            ->assertSee('href="'.url('/').'"', false);
    }

    public function test_unsupported_locale_is_ignored(): void
    {
        $this->get('/lang/fr')->assertRedirect();

        $this->assertNull(session('locale'));
        $this->get('/')->assertSee('<html lang="vi">', false);
    }

    public function test_post_content_follows_selected_language(): void
    {
        $this->makePost('bai-song-ngu', ['vi' => 'Tiêu đề tiếng Việt', 'en' => 'English title']);

        $this->get('/bai-viet/bai-song-ngu')
            ->assertSee('Tiêu đề tiếng Việt')
            ->assertSee('Nội dung tiếng Việt', false)
            ->assertDontSee('English title');

        $this->withSession(['locale' => 'en'])
            ->get('/en/posts/bai-song-ngu')
            ->assertSee('English title')
            ->assertSee('English content', false)
            ->assertDontSee('Tiêu đề tiếng Việt');
    }

    public function test_blog_lists_only_posts_written_in_selected_language(): void
    {
        $this->makePost('song-ngu', ['vi' => 'Bài song ngữ', 'en' => 'Bilingual post']);
        $this->makePost('chi-tieng-viet', ['vi' => 'Chỉ có tiếng Việt'], ['content' => ['vi' => '<p>Nội dung</p>']]);
        $this->makePost('english-only', ['en' => 'English only post'], ['content' => ['en' => '<p>Content</p>']]);
        $this->makePost('thieu-noi-dung-en', ['vi' => 'Thiếu nội dung tiếng Anh', 'en' => 'Missing English content'], ['content' => ['vi' => '<p>Nội dung</p>']]);

        $this->get('/blog')
            ->assertSee('Bài song ngữ')
            ->assertSee('Chỉ có tiếng Việt')
            ->assertSee('Thiếu nội dung tiếng Anh')
            ->assertDontSee('English only post');

        $this->withSession(['locale' => 'en'])
            ->get('/en/blog')
            ->assertSee('Bilingual post')
            ->assertSee('English only post')
            ->assertDontSee('Chỉ có tiếng Việt')
            ->assertDontSee('Missing English content');
    }

    public function test_post_without_translation_in_selected_language_is_not_shown(): void
    {
        $this->makePost('chi-tieng-viet', ['vi' => 'Chỉ có tiếng Việt'], ['content' => ['vi' => '<p>Nội dung</p>']]);
        $this->makePost('english-only', ['en' => 'English only post'], ['content' => ['en' => '<p>Content</p>']]);

        $this->withSession(['locale' => 'en'])
            ->get('/en/posts/chi-tieng-viet')
            ->assertRedirect('/en/blog');

        $this->withSession(['locale' => 'vi'])
            ->get('/bai-viet/english-only')
            ->assertRedirect('/blog');

        $this->withSession(['locale' => 'vi'])
            ->get('/bai-viet/chi-tieng-viet')
            ->assertOk()
            ->assertSee('Chỉ có tiếng Việt');
    }

    public function test_related_posts_only_use_selected_language(): void
    {
        $this->makePost('main', ['vi' => 'Bài chính', 'en' => 'Main post']);
        $this->makePost('related-en', ['vi' => 'Liên quan song ngữ', 'en' => 'Related English']);
        $this->makePost('related-vi', ['vi' => 'Liên quan chỉ tiếng Việt'], ['content' => ['vi' => '<p>Nội dung</p>']]);

        $this->withSession(['locale' => 'en'])
            ->get('/en/posts/main')
            ->assertSee('Related English')
            ->assertDontSee('Liên quan chỉ tiếng Việt');
    }

    public function test_blog_search_only_matches_selected_language(): void
    {
        $this->makePost('hoc-bong', ['vi' => 'Học bổng DAAD', 'en' => 'DAAD scholarship']);
        $this->makePost('visa', ['vi' => 'Thủ tục thị thực', 'en' => 'Student visa']);
        $this->makePost('noi-bat', ['vi' => 'Bài nổi bật', 'en' => 'Featured']);

        $this->get('/blog?q=scholarship')->assertDontSee('Học bổng DAAD');
        $this->get('/blog?q=bổng')->assertSee('Học bổng DAAD')->assertDontSee('Thủ tục thị thực');

        $this->withSession(['locale' => 'en'])
            ->get('/en/blog?q=scholarship')
            ->assertSee('DAAD scholarship')
            ->assertDontSee('Student visa');

        // "vi" là key JSON của mọi bản ghi, chỉ được khớp với nội dung thật
        $this->withSession(['locale' => 'en'])
            ->get('/en/blog?q=vi')
            ->assertDontSee('DAAD scholarship')
            ->assertSee('Student visa');
    }

    public function test_admin_saves_and_loads_translations_per_language(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => ['vi' => 'Du học Đức 2027', 'en' => 'Study in Germany 2027'],
                'content' => ['vi' => '<p>Xin chào</p>', 'en' => '<p>Hello</p>'],
                'category_id' => $this->category->id,
                'status' => PostStatus::Published->value,
            ])
            ->assertSchemaStateSet(['slug' => 'du-hoc-duc-2027'])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::where('slug', 'du-hoc-duc-2027')->firstOrFail();

        $this->assertSame('Du học Đức 2027', $post->getTranslation('title', 'vi'));
        $this->assertSame('Study in Germany 2027', $post->getTranslation('title', 'en'));

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertSchemaStateSet([
                'title.vi' => 'Du học Đức 2027',
                'title.en' => 'Study in Germany 2027',
            ]);
    }

    public function test_admin_english_post_slug_is_generated_from_english_title(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');

        // Gõ nhầm tiêu đề tiếng Việt rồi xóa => slug theo tiêu đề English
        Livewire::test(CreatePost::class)
            ->fillForm(['title' => ['vi' => 'Tiêu đề tạm']])
            ->assertSchemaStateSet(['slug' => 'tieu-de-tam'])
            ->fillForm(['title' => ['vi' => null, 'en' => 'Life in Germany for Students']])
            ->assertSchemaStateSet(['slug' => 'life-in-germany-for-students']);

        // Slug đã có bài khác dùng => thêm hậu tố
        $this->makePost('study-in-germany', ['en' => 'Study in Germany'], ['content' => ['en' => '<p>x</p>']]);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => ['en' => 'Study in Germany'],
                'content' => ['en' => '<p>Content</p>'],
                'category_id' => $this->category->id,
            ])
            ->assertSchemaStateSet(['slug' => 'study-in-germany-2'])
            ->call('create')
            ->assertHasNoFormErrors();

        // Sửa tiêu đề bài đã có => giữ slug cũ để không hỏng link
        $post = Post::where('slug', 'study-in-germany-2')->firstOrFail();

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm(['title' => ['en' => 'Studying in Germany 2027']])
            ->assertSchemaStateSet(['slug' => 'study-in-germany-2']);
    }

    public function test_admin_post_needs_at_least_one_language(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => ['vi' => 'Chỉ tiếng Việt'],
                'content' => ['vi' => '<p>Nội dung</p>'],
                'category_id' => $this->category->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Bài cho chuyên mục tiếng Anh: chỉ cần tab English, slug tự tạo từ tiêu đề tiếng Anh
        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => ['en' => 'English only'],
                'content' => ['en' => '<p>Content</p>'],
                'category_id' => $this->category->id,
            ])
            ->assertSchemaStateSet(['slug' => 'english-only'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(['en'], Post::where('slug', 'english-only')->firstOrFail()->getTranslatedLocales('title'));

        Livewire::test(CreatePost::class)
            ->fillForm(['slug' => 'trong', 'category_id' => $this->category->id])
            ->call('create')
            ->assertHasFormErrors(['title.vi' => 'required', 'title.en' => 'required']);

        Livewire::test(CreatePost::class)
            ->fillForm(['title' => ['en' => 'No content'], 'slug' => 'no-content', 'category_id' => $this->category->id])
            ->call('create')
            ->assertHasFormErrors(['content.en' => 'required'])
            ->assertHasNoFormErrors(['title.vi', 'content.vi']);
    }

    public function test_english_category_replaces_original_category_in_english_mode(): void
    {
        $overview = Category::create([
            'name' => ['vi' => 'Tổng quan', 'en' => 'Overview'],
            'slug' => 'tong-quan',
            'menu_group' => 'giao-duc-tai-duc',
            'is_in_menu' => true,
        ]);
        $english = Category::create([
            'name' => ['en' => 'German education overview'],
            'slug' => 'german-education-overview',
            'translation_of_id' => $overview->id,
        ]);
        $this->makePost('bai-tieng-viet', ['vi' => 'Bài tổng quan tiếng Việt'], ['category_id' => $overview->id, 'content' => ['vi' => '<p>Nội dung</p>']]);
        $this->makePost('english-post', ['en' => 'German education English post'], ['category_id' => $english->id, 'content' => ['en' => '<p>Content</p>']]);

        // Menu: cùng URL, tên theo ngôn ngữ
        $this->get('/')->assertSee('href="'.url('/giao-duc-tai-duc/tong-quan').'">Tổng quan</a>', false);
        $this->withSession(['locale' => 'en'])->get('/en')
            ->assertSee('href="'.url('/en/study-in-germany/overview').'">German education overview</a>', false)
            ->assertDontSee('>Tổng quan</a>', false);

        // Trang chuyên mục: tiếng Việt => bài chuyên mục gốc, tiếng Anh => bài chuyên mục tiếng Anh
        $this->withSession(['locale' => 'vi'])->get('/giao-duc-tai-duc/tong-quan')
            ->assertOk()
            ->assertSee('Bài tổng quan tiếng Việt')
            ->assertDontSee('German education English post');
        $this->withSession(['locale' => 'en'])->get('/en/study-in-germany/overview')
            ->assertOk()
            ->assertSee('German education English post')
            ->assertDontSee('Bài tổng quan tiếng Việt');

        // Breadcrumb của bài tiếng Anh dẫn về cùng trang chuyên mục (theo ngôn ngữ)
        app()->setLocale('vi');
        $this->assertSame(url('/giao-duc-tai-duc/tong-quan'), $english->pageUrl());
        app()->setLocale('en');
        $this->assertSame(url('/en/study-in-germany/overview'), $english->pageUrl());
    }

    public function test_category_without_english_version_keeps_its_own_page_in_english_mode(): void
    {
        Category::create([
            'name' => ['vi' => 'Học bổng', 'en' => 'Scholarships'],
            'slug' => 'hoc-bong',
            'menu_group' => 'giao-duc-tai-duc',
            'is_in_menu' => true,
        ]);

        $this->withSession(['locale' => 'en'])->get('/en')
            ->assertSee('href="'.url('/en/study-in-germany/scholarships').'">Scholarships</a>', false);
        $this->get('/en/study-in-germany/scholarships')->assertOk();
        // Slug tiếng Việt dưới /en (vd. vừa bấm chuyển ngôn ngữ) => chuyển về slug tiếng Anh
        $this->get('/en/study-in-germany/hoc-bong')->assertRedirect(url('/en/study-in-germany/scholarships'));
    }

    public function test_translation_link_migration_moves_english_category_posts_to_english(): void
    {
        $migration = require database_path('migrations/2026_11_12_065559_add_translation_of_id_to_categories_table.php');

        $overviewId = DB::table('categories')->insertGetId(['name' => '{"vi":"Tổng quan"}', 'slug' => 'tong-quan']);
        $englishId = DB::table('categories')->insertGetId(['name' => '{"vi":"German education overview"}', 'slug' => 'german-education-overview']);
        $postId = DB::table('posts')->insertGetId([
            'title' => '{"vi":"Primary Education in Germany"}',
            'excerpt' => '{"vi":"Short"}',
            'content' => '{"vi":"<p>English text</p>"}',
            'slug' => 'primary-education',
            'status' => PostStatus::Published->value,
            'category_id' => $englishId,
        ]);

        $migration->up();
        $migration->up(); // chạy lại không chuyển 2 lần

        $this->assertSame($overviewId, Category::find($englishId)->translation_of_id);
        $this->assertSame(['en' => 'German education overview'], Category::find($englishId)->getTranslations('name'));
        $post = Post::find($postId);
        $this->assertSame(['en' => 'Primary Education in Germany'], $post->getTranslations('title'));
        $this->assertSame(['en' => '<p>English text</p>'], $post->getTranslations('content'));
        $this->assertSame(['en' => 'Short'], $post->getTranslations('excerpt'));
    }

    public function test_migration_wraps_existing_plain_values_as_vietnamese(): void
    {
        $postId = DB::table('posts')->insertGetId([
            'title' => 'Bài viết cũ',
            'slug' => 'bai-viet-cu',
            'content' => '<p>Nội dung cũ</p>',
            'status' => PostStatus::Published->value,
        ]);
        DB::table('pages')->insert([
            'title' => 'Trang cũ',
            'slug' => 'trang-cu',
            'sections' => json_encode([['title' => 'Mục 1', 'body' => 'Nội dung 1']]),
        ]);

        $migration = require database_path('migrations/2026_11_11_092918_make_content_columns_translatable.php');
        $migration->up();
        $migration->up(); // chạy lại không bọc 2 lần

        $post = Post::findOrFail($postId);
        $this->assertSame(['vi' => 'Bài viết cũ'], $post->getTranslations('title'));
        $this->assertSame(['vi' => '<p>Nội dung cũ</p>'], $post->getTranslations('content'));
        $this->assertSame(['title' => 'Mục 1', 'body' => 'Nội dung 1'], Page::where('slug', 'trang-cu')->first()->sections[0]);
    }

    public function test_header_submenu_uses_category_name_in_selected_language(): void
    {
        Category::create([
            'name' => ['vi' => 'Tổng quan', 'en' => 'Overview'],
            'slug' => 'tong-quan',
            'menu_group' => 'giao-duc-tai-duc',
            'is_in_menu' => true,
        ]);

        $this->get('/')->assertSee('>Tổng quan</a>', false);

        $this->withSession(['locale' => 'en'])
            ->get('/en')
            ->assertSee('>Overview</a>', false)
            ->assertDontSee('>Tổng quan</a>', false);
    }

    public function test_english_category_names_migration_fills_only_missing_translations(): void
    {
        $overview = Category::create(['name' => ['vi' => 'Tổng quan'], 'slug' => 'tong-quan']);
        $visa = Category::create(['name' => ['vi' => 'Quy trình cấp Visa'], 'slug' => 'quy-trinh-cap-visa']);
        $custom = Category::create(['name' => ['vi' => 'Du học Đại học', 'en' => 'Undergraduate'], 'slug' => 'du-hoc-dai-hoc']);

        $migration = require database_path('migrations/2026_11_12_054130_add_english_names_to_menu_categories.php');
        $migration->up();

        $this->assertSame('Overview', $overview->fresh()->getTranslation('name', 'en'));
        $this->assertSame('Visa Application Process', $visa->fresh()->getTranslation('name', 'en'));
        $this->assertSame('Undergraduate', $custom->fresh()->getTranslation('name', 'en'));
        $this->assertSame('Tổng quan', $overview->fresh()->getTranslation('name', 'vi'));
    }
}
