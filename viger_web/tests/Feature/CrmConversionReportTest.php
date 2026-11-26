<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmConversionReportTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 25)->setTime(10, 0));
        $this->user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);
        $this->actingAs($this->user);
    }

    /** Tạo lead ngày $created rồi đổi trạng thái lần lượt [ngày => trạng thái] (ghi lịch sử thật). */
    private function journey(string $created, array $steps, array $attrs = []): Lead
    {
        $lead = Lead::factory()->create(array_merge(['created_at' => $created.' 08:00', 'status' => 'new'], $attrs));
        foreach ($steps as $date => $status) {
            $this->travelTo($date.' 08:00');
            $lead->moveToStatus($status);
        }
        $this->travelTo('2026-11-25 10:00');

        return $lead;
    }

    public function test_funnel_counts_stages_ever_reached(): void
    {
        // A: đi hết tới chốt
        $this->journey('2026-11-02', ['2026-11-03' => 'contacted', '2026-11-05' => 'consulting', '2026-11-12' => 'converted'], ['source' => 'website']);
        // B: tới Đang tư vấn rồi bị loại => vẫn tính đã qua Liên hệ, Tư vấn
        $this->journey('2026-11-04', ['2026-11-05' => 'contacted', '2026-11-06' => 'consulting', '2026-11-10' => 'lost'], ['source' => 'website']);
        // C: kéo thẳng Mới → Tiềm năng => coi như qua các bước trước
        $this->journey('2026-11-06', ['2026-11-08' => 'potential'], ['source' => 'holland']);
        // D: còn Mới
        $this->journey('2026-11-07', [], ['source' => 'holland']);

        $res = $this->get(self::CRM.'/reports/conversion')->assertOk()->assertSee('Phễu chuyển đổi');
        $f = $res->viewData('funnel');

        $this->assertSame(4, $res->viewData('size'));
        $this->assertSame([4, 3, 3, 2, 1], array_column($f, 'count'));
        $this->assertEquals(75.0, $f['contacted']['step']);
        $this->assertEquals(25.0, $f['converted']['pct']);
        $this->assertSame(1, $res->viewData('lost'));

        // Thời gian tới "Đã chuyển đổi" của A: 10 ngày
        $this->assertEquals(10.0, $res->viewData('timing')['converted']['days']);
        // Tới "Đã liên hệ": A 1 ngày, B 1 ngày => trung vị 1 (C, D không có dòng contacted)
        $this->assertEquals(1.0, $res->viewData('timing')['contacted']['days']);

        $bySource = $res->viewData('bySource');
        $this->assertSame(1, $bySource['website']['converted']);
        $this->assertSame(1, $bySource['website']['lost']);
        $this->assertEquals(50.0, $bySource['website']['rate']);
    }

    public function test_seeded_status_without_history_still_counts(): void
    {
        // Dữ liệu seed: tạo thẳng status converted, không có lịch sử đổi trạng thái
        Lead::factory()->create(['status' => 'converted', 'created_at' => '2026-11-10']);

        $res = $this->get(self::CRM.'/reports/conversion');
        $this->assertSame([1, 1, 1, 1, 1], array_column($res->viewData('funnel'), 'count'));
        $this->assertNull($res->viewData('timing')['converted']['days']);   // không có thời điểm
    }

    public function test_monthly_cohorts_last_six_months(): void
    {
        $this->journey('2026-10-05', ['2026-11-02' => 'converted']);
        $this->journey('2026-10-08', ['2026-10-20' => 'lost']);
        Lead::factory()->create(['created_at' => '2026-11-03', 'status' => 'consulting']);

        $cohorts = $this->get(self::CRM.'/reports/conversion')->viewData('cohorts');

        $this->assertCount(6, $cohorts);
        $oct = $cohorts[4];
        $this->assertSame('Tháng 10/2026', $oct['label']);
        $this->assertSame([2, 1, 1, 0], [$oct['size'], $oct['converted'], $oct['lost'], $oct['open']]);
        $this->assertEquals(50.0, $oct['rate']);
        $this->assertSame(1, $cohorts[5]['open']);
    }

    public function test_empty_period(): void
    {
        $this->get(self::CRM.'/reports/conversion?period=last_month')
            ->assertOk()
            ->assertSee('Chưa có khách trong kỳ');
    }
}
