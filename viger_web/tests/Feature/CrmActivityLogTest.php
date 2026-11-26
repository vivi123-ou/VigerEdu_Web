<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['crm_role' => 'admin', 'active' => true, 'full_name' => 'Tư Vấn A']);
    }

    public function test_feed_lists_activities_of_all_leads(): void
    {
        $a = Lead::factory()->create(['full_name' => 'Khách Một']);
        $b = Lead::factory()->create(['full_name' => 'Khách Hai']);
        $a->log('Gọi lần đầu', 'call', $this->user->id);
        $b->log('Ghi chú nhu cầu', 'note', $this->user->id);

        $res = $this->actingAs($this->user)->get(self::CRM.'/activities')->assertOk()
            ->assertSee('Gọi lần đầu')->assertSee('Ghi chú nhu cầu')
            ->assertSee('Khách Một')->assertSee('Tư Vấn A')
            ->assertSee('Hôm nay');

        // 2 dòng "CRM tiếp nhận" (hệ thống) + 1 call + 1 note
        $this->assertSame(4, $res->viewData('counts')['all']);
        $this->assertSame(2, $res->viewData('counts')['system']);
    }

    public function test_filter_by_type_user_and_search(): void
    {
        $colleague = User::factory()->create(['crm_role' => 'admin', 'active' => true]);
        $a = Lead::factory()->create(['full_name' => 'Khách Một', 'phone' => '0911111111']);
        $b = Lead::factory()->create(['full_name' => 'Khách Hai']);
        $a->log('Cuộc gọi của tôi', 'call', $this->user->id);
        $b->log('Ghi chú của đồng nghiệp', 'note', $colleague->id);

        $this->actingAs($this->user);

        $this->get(self::CRM.'/activities?type=call')->assertSee('Cuộc gọi của tôi')->assertDontSee('<p class="tl-content">Ghi chú của đồng nghiệp', false);
        $this->get(self::CRM.'/activities?user='.$this->user->id)->assertSee('Cuộc gọi của tôi')->assertDontSee('<p class="tl-content">Ghi chú của đồng nghiệp', false);
        $this->get(self::CRM.'/activities?user=system')->assertSee('CRM tiếp nhận')->assertDontSee('<p class="tl-content">Cuộc gọi của tôi', false);
        $this->get(self::CRM.'/activities?q=0911111111')->assertSee('Cuộc gọi của tôi')->assertDontSee('<p class="tl-content">Ghi chú của đồng nghiệp', false);
    }

    public function test_period_filter(): void
    {
        $lead = Lead::factory()->create();
        $old = $lead->log('Hoạt động cũ', 'note', $this->user->id);
        $old->forceFill(['created_at' => now()->subDays(20)])->save();
        $lead->log('Hoạt động mới', 'note', $this->user->id);

        $this->actingAs($this->user)->get(self::CRM.'/activities?period=7d')
            ->assertSee('Hoạt động mới')->assertDontSee('<p class="tl-content">Hoạt động cũ', false);
    }

    public function test_invalid_filters_are_ignored_and_empty_state(): void
    {
        $this->actingAs($this->user)->get(self::CRM.'/activities?type=hack&period=x')
            ->assertOk()
            ->assertSee('Không có hoạt động nào');
    }
}
