<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EducationPageLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_education_page_shows_vietnamese_content_by_default(): void
    {
        $this->get('/giao-duc-tai-duc')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Giáo dục tại Đức', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('GIÁO DỤC TẠI ĐỨC')
            ->assertSee('Miễn Phí 100% Học Phí')
            ->assertSee('Sẵn Sàng Chinh Phục Giảng Đường Đức?')
            ->assertDontSee('Why Do Students Love Germany?');
    }

    public function test_education_page_shows_english_content_in_english_mode(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/en/study-in-germany')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Study in Germany', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('STUDY IN GERMANY')
            ->assertSee('Why Do Students Love Germany?')
            ->assertSee('100% Tuition-Free')
            ->assertSee('Ready to Conquer a German University?')
            ->assertDontSee('Miễn Phí 100% Học Phí')
            ->assertDontSee('Sẵn Sàng Chinh Phục Giảng Đường Đức?');
    }

    public function test_english_mode_ignores_page_content_that_only_exists_in_vietnamese(): void
    {
        Page::factory()->create([
            'slug' => 'giao-duc-tai-duc',
            'title' => ['vi' => 'Tiêu đề từ admin'],
            'subtitle' => ['vi' => 'Mô tả từ admin'],
        ]);

        $this->get('/giao-duc-tai-duc')
            ->assertSee('Tiêu đề từ admin')
            ->assertSee('Mô tả từ admin');

        $this->withSession(['locale' => 'en'])
            ->get('/en/study-in-germany')
            ->assertSee('STUDY IN GERMANY')
            ->assertDontSee('Tiêu đề từ admin')
            ->assertDontSee('Mô tả từ admin');
    }

    public function test_english_mode_uses_page_english_title_when_available(): void
    {
        Page::factory()->create([
            'slug' => 'giao-duc-tai-duc',
            'title' => ['vi' => 'Tiêu đề từ admin', 'en' => 'Admin English title'],
        ]);

        $this->withSession(['locale' => 'en'])
            ->get('/en/study-in-germany')
            ->assertSee('Admin English title')
            ->assertDontSee('Tiêu đề từ admin');
    }
}
