<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_shows_vietnamese_content_by_default(): void
    {
        $this->get('/ve-chung-toi')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Về chúng tôi', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('Câu chuyện của chúng tôi')
            ->assertSee('Cam Kết Của Chúng Tôi Đối Với Sự Thành Công Của Bạn')
            ->assertDontSee('Our story');
    }

    public function test_about_page_shows_english_content_in_english_mode(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/ve-chung-toi')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'About', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('Our story')
            ->assertSee('Discover Your Pathway into Higher Education')
            ->assertSee('Our Commitment to Your Success')
            ->assertSee('A Legacy Shaping the Future Through Education')
            ->assertSee('Begin your journey toward the future')
            ->assertDontSee('Câu chuyện của chúng tôi')
            ->assertDontSee('Cam Kết Của Chúng Tôi Đối Với Sự Thành Công Của Bạn')
            ->assertDontSee('Khởi đầu hành trình chinh phục tương lai');
    }

    public function test_saved_english_translation_overrides_default(): void
    {
        Setting::set('about_hero_pretitle_en', 'Who we are');

        $this->withSession(['locale' => 'en'])
            ->get('/ve-chung-toi')
            ->assertOk()
            ->assertSee('Who we are')
            ->assertDontSee('Our story');
    }
}
