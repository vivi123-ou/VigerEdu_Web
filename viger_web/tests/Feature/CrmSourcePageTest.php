<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmSourcePageTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);
    }

    public function test_website_page_shows_stats_for_that_source_only(): void
    {
        Lead::factory()->count(2)->create(['source' => 'website', 'status' => 'new']);
        Lead::factory()->create(['source' => 'website', 'status' => 'converted', 'full_name' => 'Khách Web Chốt']);
        Lead::factory()->create(['source' => 'holland', 'full_name' => 'Khách Holland']);

        $res = $this->actingAs($this->user)->get(self::CRM.'/sources/website')->assertOk()
            ->assertSee('Website VIGER')
            ->assertSee('Khách Web Chốt')
            ->assertDontSee('>Khách Holland</a>', false);

        $stats = $res->viewData('stats');
        $this->assertSame(3, $stats['total']);
        $this->assertSame(2, $stats['new']);
        $this->assertSame(1, $stats['converted']);
        $this->assertEquals(33.3, $stats['rate']);
        $this->assertEquals(75.0, $stats['share']);
        $this->assertSame(2, $res->viewData('funnel')['new']['count']);
    }

    public function test_channel_status_from_intake_events(): void
    {
        config(['crm.api_channels.0.token_hash' => hash('sha256', 'x'), 'crm.api_channels.0.enabled' => true]);

        // Chưa nhận gì => cảnh báo
        $this->actingAs($this->user)->get(self::CRM.'/sources/website')->assertSee('chưa nhận được khách nào');

        $lead = Lead::factory()->create(['source' => 'website']);
        DB::table('crm_intake_events')->insert([
            'event_id' => (string) Str::uuid(), 'channel' => 'website', 'lead_id' => $lead->id,
            'response' => '{}', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $res = $this->get(self::CRM.'/sources/website')->assertSee('Kết nối hoạt động bình thường');
        $this->assertSame(1, $res->viewData('channel')['count_30d']);

        config(['crm.api_channels.0.enabled' => false]);
        $this->get(self::CRM.'/sources/website')->assertSee('Kênh đang tắt');
    }

    public function test_recent_form_messages(): void
    {
        $lead = Lead::factory()->create(['source' => 'website']);
        $lead->log('Muốn hỏi về du học nghề điều dưỡng', 'form');

        $this->actingAs($this->user)->get(self::CRM.'/sources/website')
            ->assertSee('Muốn hỏi về du học nghề điều dưỡng');
    }

    public function test_menu_highlights_only_website_and_unknown_source_404(): void
    {
        $html = $this->actingAs($this->user)->get(self::CRM.'/sources/website')->getContent();

        $this->assertStringContainsString('href="'.self::CRM.'/sources/website"', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));

        $this->get(self::CRM.'/sources/tiktok')->assertNotFound();
    }

    public function test_other_sources_work_without_channel(): void
    {
        $this->actingAs($this->user)->get(self::CRM.'/sources/other')
            ->assertOk()
            ->assertSee('Nguồn khác')
            ->assertDontSee('channel-card', false);
    }
}
