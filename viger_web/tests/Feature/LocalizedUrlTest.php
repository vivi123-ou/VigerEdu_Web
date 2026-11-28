<?php

namespace Tests\Feature;

use App\Support\LocalizedUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocalizedUrlTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string, 1: string}> */
    public static function pages(): array
    {
        return [
            'home' => ['/', '/en'],
            'about' => ['/ve-chung-toi', '/en/about-us'],
            'education' => ['/giao-duc-tai-duc', '/en/study-in-germany'],
            'overview' => ['/giao-duc-tai-duc/tong-quan', '/en/study-in-germany/overview'],
            'consult process' => ['/tu-van/quy-trinh-tu-van', '/en/consulting/consulting-process'],
            'programs' => ['/chuong-trinh-hoc', '/en/study-programs'],
            'contact' => ['/lien-he', '/en/contact'],
            'blog' => ['/blog', '/en/blog'],
        ];
    }

    #[DataProvider('pages')]
    public function test_each_page_has_an_english_url(string $vietnamesePath, string $englishPath): void
    {
        $this->assertSame(url($englishPath), LocalizedUrl::to($vietnamesePath, 'en'));
        $this->assertSame(url($vietnamesePath), LocalizedUrl::switchTo('vi', $englishPath));

        $this->get($englishPath)->assertOk()->assertSee('<html lang="en">', false);
        $this->get($vietnamesePath)->assertOk()->assertSee('<html lang="vi">', false);
    }

    public function test_url_decides_language_over_remembered_choice(): void
    {
        $this->withSession(['locale' => 'en'])->get('/ve-chung-toi')->assertSee('<html lang="vi">', false);
        $this->withSession(['locale' => 'vi'])->get('/en/about-us')
            ->assertSee('<html lang="en">', false)
            ->assertSessionHas('locale', 'en');
    }

    public function test_links_on_english_pages_use_english_urls(): void
    {
        $this->get('/en/about-us')
            ->assertSee('href="'.url('/en/contact').'"', false)
            ->assertSee('href="'.url('/en/study-programs').'"', false)
            ->assertDontSee('href="'.url('/lien-he').'"', false)
            // Nút chuyển ngôn ngữ => cùng trang bản tiếng Việt
            ->assertSee('class="lang-toggle"', false)
            ->assertSee('href="'.url('/ve-chung-toi').'"', false)
            ->assertSee('<link rel="alternate" hreflang="vi" href="'.url('/ve-chung-toi').'" />', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/en/about-us').'" />', false);
    }

    public function test_switch_keeps_query_string_and_leaves_external_links_alone(): void
    {
        $this->assertSame(url('/en/blog').'?q=visa&page=2', LocalizedUrl::switchTo('en', '/blog?q=visa&page=2'));
        $this->assertSame(url('/en/posts/my-post'), LocalizedUrl::to('/bai-viet/my-post', 'en'));
        $this->assertSame(url('/en/contact').'#dang-ky', LocalizedUrl::to('/lien-he#dang-ky', 'en'));
        $this->assertSame('https://facebook.com/x', LocalizedUrl::to('https://facebook.com/x', 'en'));
        $this->assertSame('tel:0123', LocalizedUrl::to('tel:0123', 'en'));
        // Link admin nhập sẵn có tiền tố ngôn ngữ => giữ nguyên
        $this->assertSame(url('/en/contact'), LocalizedUrl::to('/en/contact', 'vi'));
    }

    public function test_old_language_link_redirects_to_matching_page(): void
    {
        $this->from(url('/giao-duc-tai-duc/tong-quan'))
            ->get('/lang/en')
            ->assertRedirect(url('/en/study-in-germany/overview'));

        $this->from(url('/en/contact'))
            ->get('/lang/vi')
            ->assertRedirect(url('/lien-he'));
    }
}
