<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmChatbotSourcePageTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);
    }

    public function test_chatbot_page_with_hourly_chart_and_chat_excerpts(): void
    {
        // Factory gán created_at ngẫu nhiên => đặt rõ 20:15 hôm qua
        $this->travelTo(now()->subDay()->setTime(20, 15));
        $lead = Lead::factory()->create(['source' => 'chatbot', 'full_name' => 'Khách Chat Tối', 'created_at' => now()]);
        $lead->log("Khách để lại SĐT qua AI Chatbot.\nKhách: Học phí ở Berlin bao nhiêu?", 'form');
        $this->travelBack();

        Lead::factory()->create(['source' => 'website', 'created_at' => now()->setTime(20, 30)]);   // khác nguồn

        $res = $this->actingAs($this->user)->get(self::CRM.'/sources/chatbot')->assertOk()
            ->assertSee('AI Chatbot')
            ->assertSee('Khách để lại thông tin theo giờ')
            ->assertSee('cao điểm 20h–21h')
            ->assertSee('Trích đoạn chat gần đây')
            ->assertSee('Học phí ở Berlin bao nhiêu?')
            ->assertSee('Khách Chat Tối');

        $byHour = $res->viewData('byHour');
        $this->assertCount(24, $byHour);
        $this->assertSame(1, $byHour[20]);
        $this->assertSame(1, array_sum($byHour));
    }

    public function test_hourly_chart_only_on_chatbot_page(): void
    {
        $this->actingAs($this->user)->get(self::CRM.'/sources/website')
            ->assertOk()
            ->assertDontSee('Khách để lại thông tin theo giờ')
            ->assertSee('Lời nhắn gần đây');
    }

    public function test_menu_link_and_channel_status(): void
    {
        config(['crm.api_channels.1.token_hash' => hash('sha256', 'x')]);   // kênh chatbot

        $this->actingAs($this->user)->get(self::CRM.'/sources/chatbot')
            ->assertSee('href="'.self::CRM.'/sources/chatbot"', false)
            ->assertDontSee('/api/v1')
            ->assertSee('chưa nhận được khách nào');
    }
}
