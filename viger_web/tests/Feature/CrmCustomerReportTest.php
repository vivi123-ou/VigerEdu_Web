<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmCustomerReportTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Cố định ngày giữa tháng để "tháng này / tháng trước" ổn định
        $this->travelTo(now()->setDate(2026, 11, 20)->setTime(10, 0));
        $this->user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);
    }

    private function lead(string $date, array $attrs = []): Lead
    {
        return Lead::factory()->create(array_merge(['created_at' => $date, 'status' => 'new'], $attrs));
    }

    private function convert(Lead $lead, string $date): void
    {
        $this->travelTo($date);
        $lead->moveToStatus('converted');
        $this->travelTo('2026-11-20 10:00');
    }

    public function test_metrics_this_month_vs_last_month(): void
    {
        $a = $this->lead('2026-11-03', ['source' => 'website']);
        $this->lead('2026-11-10', ['source' => 'website']);
        $this->lead('2026-11-12', ['source' => 'holland']);
        $this->lead('2026-11-15', ['source' => 'holland']);
        $this->lead('2026-10-05');   // tháng trước
        $this->convert($a, '2026-11-18 09:00');

        $res = $this->actingAs($this->user)->get(self::CRM.'/reports/customers')->assertOk()
            ->assertSee('01/11/2026 – 20/11/2026');

        $this->assertSame(4, $res->viewData('current')['new']);
        $this->assertSame(1, $res->viewData('current')['converted']);
        $this->assertEquals(25.0, $res->viewData('current')['rate']);
        // Kỳ trước cùng độ dài 20 ngày: 12/10 – 31/10 => lead 05/10 nằm ngoài
        $this->assertSame(0, $res->viewData('previous')['new']);

        $sources = $res->viewData('bySource');
        $this->assertSame(2, $sources['website']['new']);
        $this->assertSame(1, $sources['website']['converted']);
        $this->assertEquals(50.0, $sources['website']['rate']);
        $this->assertSame(2, $sources['holland']['new']);
    }

    public function test_daily_trend_for_short_period_monthly_for_long(): void
    {
        $this->lead('2026-11-03');
        $this->lead('2026-11-03');

        $trend = $this->actingAs($this->user)->get(self::CRM.'/reports/customers')->viewData('trend');
        $this->assertTrue($trend['daily']);
        $this->assertCount(20, $trend['labels']);
        $this->assertSame(2, $trend['new'][2]);   // 03/11

        $year = $this->get(self::CRM.'/reports/customers?period=this_year')->viewData('trend');
        $this->assertFalse($year['daily']);
        $this->assertCount(11, $year['labels']);   // T1 – T11
        $this->assertSame(2, $year['new'][10]);
    }

    public function test_custom_period_and_invalid_range_fallback(): void
    {
        $this->lead('2026-09-10');
        $this->lead('2026-09-25');

        $res = $this->actingAs($this->user)->get(self::CRM.'/reports/customers?period=custom&from=2026-09-01&to=2026-09-15');
        $this->assertSame('custom', $res->viewData('period'));
        $this->assertSame(1, $res->viewData('current')['new']);

        // Ngày đảo ngược => về Tháng này
        $this->assertSame('this_month', $this->get(self::CRM.'/reports/customers?period=custom&from=2026-09-15&to=2026-09-01')->viewData('period'));
    }

    public function test_profile_breakdown(): void
    {
        $this->lead('2026-11-05', ['program_interest' => 'Đại học', 'german_level' => 'B1']);
        $this->lead('2026-11-06', ['program_interest' => 'Đại học', 'german_level' => 'A2']);
        $this->lead('2026-11-07', ['program_interest' => 'Du học nghề (Ausbildung)']);

        $res = $this->actingAs($this->user)->get(self::CRM.'/reports/customers')
            ->assertSee('Chân dung khách mới trong kỳ')
            ->assertSee('Du học nghề (Ausbildung)');

        $this->assertSame(['Đại học' => 2, 'Du học nghề (Ausbildung)' => 1], $res->viewData('profile')['program_interest']['items']->all());
    }

    public function test_empty_report_renders(): void
    {
        $this->actingAs($this->user)->get(self::CRM.'/reports/customers?period=last_month')
            ->assertOk()
            ->assertSee('Chưa có dữ liệu trong kỳ');
    }
}
