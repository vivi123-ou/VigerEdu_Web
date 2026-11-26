<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmConvertedListTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);
    }

    private function row(string $name): string
    {
        return 'class="link-strong">'.$name.'</a>';
    }

    /** Tạo lead tiếp nhận $receivedDaysAgo ngày trước, chuyển đổi $convertedDaysAgo ngày trước (qua trang sửa thật). */
    private function convertedLead(string $name, int $receivedDaysAgo, int $convertedDaysAgo, array $attrs = []): Lead
    {
        $this->travelTo(now()->subDays($receivedDaysAgo));
        $lead = Lead::factory()->create(['full_name' => $name, 'status' => 'consulting', 'created_at' => now()] + $attrs);
        $this->travelBack();

        $this->travelTo(now()->subDays($convertedDaysAgo));
        $this->actingAs($this->user)->put(self::CRM."/leads/{$lead->id}/edit/general", [
            'source' => $lead->source, 'status' => 'converted', 'assigned_to' => $lead->assigned_to,
        ]);
        $this->travelBack();

        return $lead->fresh();
    }

    public function test_lists_only_converted_with_conversion_date_and_days(): void
    {
        $this->convertedLead('Đã Chốt', 30, 10);
        Lead::factory()->create(['full_name' => 'Còn Tư Vấn', 'status' => 'consulting']);

        $res = $this->actingAs($this->user)->get(self::CRM.'/converted')->assertOk()
            ->assertSee($this->row('Đã Chốt'), false)
            ->assertDontSee($this->row('Còn Tư Vấn'), false)
            ->assertSee(now()->subDays(10)->format('d/m/Y'))
            ->assertSee('20 ngày');

        $summary = $res->viewData('summary');
        $this->assertSame(1, $summary['total']);
        $this->assertSame(20, $summary['avg_days']);
        $this->assertSame(50.0, (float) $summary['rate']);
    }

    public function test_sorted_by_most_recent_conversion(): void
    {
        $this->convertedLead('Chốt Lâu', 60, 40);
        $this->convertedLead('Chốt Gần', 20, 2);

        $this->actingAs($this->user)->get(self::CRM.'/converted')
            ->assertSeeInOrder([$this->row('Chốt Gần'), $this->row('Chốt Lâu')], false);
    }

    public function test_period_and_source_filters(): void
    {
        $this->convertedLead('Chốt Gần', 20, 3, ['source' => 'website']);
        $this->convertedLead('Chốt Lâu', 200, 120, ['source' => 'holland']);

        $this->actingAs($this->user);

        $this->get(self::CRM.'/converted?period=90d')
            ->assertSee($this->row('Chốt Gần'), false)
            ->assertDontSee($this->row('Chốt Lâu'), false);

        $this->get(self::CRM.'/converted?source=holland')
            ->assertSee($this->row('Chốt Lâu'), false)
            ->assertDontSee($this->row('Chốt Gần'), false);
    }

    public function test_lead_without_status_log_uses_estimated_date(): void
    {
        // VD dữ liệu seed: tạo thẳng với status converted, không có dòng "Đổi trạng thái"
        Lead::factory()->create(['full_name' => 'Không Lịch Sử', 'status' => 'converted']);

        $this->actingAs($this->user)->get(self::CRM.'/converted')
            ->assertOk()
            ->assertSee($this->row('Không Lịch Sử'), false)
            ->assertSee('ước tính');
    }

    public function test_empty_state(): void
    {
        $this->actingAs($this->user)->get(self::CRM.'/converted')
            ->assertOk()
            ->assertSee('Chưa có khách hàng chuyển đổi');
    }
}
