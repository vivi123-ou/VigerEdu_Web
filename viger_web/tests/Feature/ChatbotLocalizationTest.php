<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_is_vietnamese_by_default(): void
    {
        $this->get('/lien-he')
            ->assertOk()
            ->assertSee('Trợ lý VIGER AI')
            ->assertSee('Chi phí du học Đức')
            ->assertSee('placeholder="Nhập câu hỏi của bạn..."', false)
            ->assertDontSee('VIGER AI Assistant');
    }

    public function test_chatbot_is_english_in_english_mode(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get('/lien-he')
            ->assertOk()
            ->assertSee('VIGER AI Assistant')
            ->assertSee('Online')
            ->assertSee('Cost of studying in Germany')
            ->assertSee('placeholder="Type your question..."', false)
            ->assertDontSee('Trợ lý VIGER AI')
            ->assertDontSee('Chi phí du học Đức');

        // Câu trả lời tự động (truyền sang JS) cũng là tiếng Anh
        preg_match("/data-i18n='([^']*)'/", $response->getContent(), $m);
        $i18n = json_decode(html_entity_decode($m[1]), true);
        $this->assertStringContainsString('no tuition fees', $i18n['replies']['cost']);
    }

    public function test_chatbot_endpoint_is_empty_when_service_not_configured(): void
    {
        config(['services.chatbot.url' => null]);

        $this->get('/lien-he')->assertOk()->assertSee('data-endpoint=""', false);
    }

    public function test_chatbot_uses_service_endpoint_in_vietnamese_mode(): void
    {
        config(['services.chatbot.url' => 'https://chat.example.test/']);

        $this->get('/lien-he')->assertOk()
            ->assertSee('data-endpoint="https://chat.example.test/chat"', false);
    }

    public function test_chatbot_keeps_canned_replies_in_english_mode(): void
    {
        config(['services.chatbot.url' => 'https://chat.example.test']);

        $this->withSession(['locale' => 'en'])->get('/lien-he')->assertOk()
            ->assertSee('data-endpoint=""', false);
    }
}
