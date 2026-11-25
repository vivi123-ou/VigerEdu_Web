<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * viger_chatbot gửi khách (lead/2, channel chatbot) khi khách để lại SĐT trong khung chat.
 */
class CrmChatbotLeadTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'http://crm.viger.test/api/v1/leads';

    private const TOKEN = 'token-chatbot-test';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.crm_domain' => 'crm.viger.test',
            'crm.api_channels.1.token_hash' => hash('sha256', self::TOKEN),
        ]);
    }

    private function payload(array $override = []): array
    {
        return array_replace_recursive([
            'schema_version' => 'lead/2',
            'event_id' => (string) Str::uuid(),
            'channel' => 'chatbot',
            'consent' => ['agreed' => true, 'text' => 'Khách tự gửi SĐT trong khung chat để được tư vấn.'],
            'contact' => ['phone' => '0912345678'],
            'extra_fields' => ['loi_nhan' => "Khách để lại SĐT qua AI Chatbot.\nKhách: Trường ở Berlin"],
        ], $override);
    }

    public function test_chatbot_lead_is_visible_with_phone_and_chat_source(): void
    {
        $this->postJson(self::URL, $this->payload(), ['X-Api-Token' => self::TOKEN])
            ->assertCreated()
            ->assertJsonPath('is_new', true);

        $lead = Lead::sole();
        $this->assertSame('0912345678', $lead->phone);
        $this->assertSame('chatbot', $lead->source);
        $this->assertStringContainsString('Trường ở Berlin', $lead->note);
    }

    public function test_chatbot_token_cannot_send_as_website(): void
    {
        $this->postJson(self::URL, $this->payload(['channel' => 'website']), ['X-Api-Token' => self::TOKEN])
            ->assertForbidden();
    }

    public function test_disabled_chatbot_channel_is_rejected(): void
    {
        config(['crm.api_channels.1.enabled' => false]);

        $this->postJson(self::URL, $this->payload(), ['X-Api-Token' => self::TOKEN])
            ->assertForbidden();
    }
}
