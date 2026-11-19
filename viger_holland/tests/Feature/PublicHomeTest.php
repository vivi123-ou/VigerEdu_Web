<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Modules\Assessment\Support\TestConfig;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_page_renders_mobile_first_with_seeded_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">', false)
            ->assertSee('<html lang="vi">', false)
            ->assertSee('Bạn hợp với hướng ngành nào khi')
            ->assertSee('du học Đức?')
            ->assertSee('36 câu hỏi ngắn')
            ->assertSee('Về trang chủ')
            ->assertSee('https://viger.edu.vn', false)
            ->assertSee('<div id="viger-chatbot"></div>', false);
    }

    public function test_texts_come_from_settings_not_code(): void
    {
        Setting::put('site_name', 'Trung tâm Thử Nghiệm');
        Setting::put('intro_title', 'Tiêu đề do Thầy sửa');
        Setting::put('main_site_url', 'https://vi-du.test');

        $this->get('/')
            ->assertOk()
            ->assertSee('Trung tâm Thử Nghiệm')
            ->assertSee('Tiêu đề do Thầy sửa')
            ->assertSee('https://vi-du.test', false)
            ->assertDontSee('Bạn hợp với hướng ngành nào khi');
    }

    public function test_question_count_on_the_start_page_follows_the_test_settings(): void
    {
        TestConfig::save(['items_per_group' => 5] + TestConfig::current(), null, 'Đổi số câu');

        $this->get('/')
            ->assertOk()
            ->assertSee('30 câu hỏi ngắn')
            ->assertDontSee('{so_cau}')
            ->assertDontSee('giới hạn thời gian');
    }

    public function test_chatbot_embed_code_is_inserted_only_when_configured(): void
    {
        $this->get('/')->assertDontSee('chat.example.test/widget.js', false);

        Setting::put('chatbot_embed_code', '<script src="https://chat.example.test/widget.js" defer></script>', 'integrations');

        $this->get('/')->assertSee('<script src="https://chat.example.test/widget.js" defer></script>', false);
    }

    public function test_home_page_is_indexable(): void
    {
        $this->get('/')
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertDontSee('noindex', false);
    }
}
