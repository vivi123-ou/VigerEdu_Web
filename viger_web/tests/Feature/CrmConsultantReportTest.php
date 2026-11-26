<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmConsultantReportTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $an;

    private User $binh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 20)->setTime(10, 0));
        $this->an = User::factory()->create(['crm_role' => 'admin', 'active' => true, 'full_name' => 'An Tư Vấn']);
        $this->binh = User::factory()->create(['crm_role' => 'admin', 'active' => true, 'full_name' => 'Bình Tư Vấn']);
    }

    private function row(string $name, $res): array
    {
        return $res->viewData('rows')->first(fn ($r) => $r['user']->full_name === $name);
    }

    public function test_metrics_per_consultant(): void
    {
        // An: 2 khách mới trong kỳ, gọi 1 khách sau 3 giờ, khách kia sau 5 giờ; chốt 1
        $l1 = Lead::factory()->create(['assigned_to' => $this->an->id, 'status' => 'consulting', 'created_at' => '2026-11-10 08:00']);
        $l2 = Lead::factory()->create(['assigned_to' => $this->an->id, 'status' => 'contacted', 'created_at' => '2026-11-12 08:00']);
        $l1->log('Gọi lần đầu', 'call', $this->an->id)->forceFill(['created_at' => '2026-11-10 11:00'])->save();
        $l2->log('Ghi chú', 'note', $this->an->id)->forceFill(['created_at' => '2026-11-12 13:00'])->save();

        $this->travelTo('2026-11-18 09:00');
        $this->actingAs($this->an);
        $l1->moveToStatus('converted');
        $this->travelTo('2026-11-20 10:00');

        Appointment::create(['lead_id' => $l2->id, 'user_id' => $this->an->id, 'scheduled_at' => '2026-11-15 09:00', 'duration_minutes' => 30, 'type' => 'call', 'status' => 'done']);
        Appointment::create(['lead_id' => $l2->id, 'user_id' => $this->an->id, 'scheduled_at' => '2026-11-16 09:00', 'duration_minutes' => 30, 'type' => 'call', 'status' => 'no_show']);

        // Bình: 1 khách đang chăm sóc, không tương tác
        Lead::factory()->create(['assigned_to' => $this->binh->id, 'status' => 'new', 'created_at' => '2026-11-01 08:00']);

        $res = $this->actingAs($this->an)->get(self::CRM.'/reports/consultants')->assertOk()
            ->assertSee('An Tư Vấn')->assertSee('Bình Tư Vấn');

        $an = $this->row('An Tư Vấn', $res);
        $this->assertSame(2, $an['new_assigned']);
        $this->assertSame(1, $an['open']);              // l2 (l1 đã chốt)
        $this->assertSame(1, $an['activities']['call']);
        $this->assertSame(2, $an['activities_total']);
        $this->assertSame(1, $an['appt_done']);
        $this->assertSame(1, $an['appt_no_show']);
        $this->assertSame(1, $an['converted']);
        $this->assertEquals(50.0, $an['rate']);
        $this->assertEquals(4.0, $an['first_response_h']);   // trung vị của 3 giờ và 5 giờ

        $binh = $this->row('Bình Tư Vấn', $res);
        $this->assertSame(1, $binh['idle']);
        $this->assertNull($binh['first_response_h']);

        // Sắp theo khách chốt: An đứng đầu
        $this->assertSame('An Tư Vấn', $res->viewData('rows')->first()['user']->full_name);
    }

    public function test_inactive_people_hidden_and_unassigned_total(): void
    {
        User::factory()->create(['crm_role' => 'admin', 'active' => true, 'full_name' => 'Kỹ Thuật Không Làm Gì']);
        Lead::factory()->create(['assigned_to' => null, 'status' => 'new']);

        $res = $this->actingAs($this->an)->get(self::CRM.'/reports/consultants')->assertOk();

        $this->assertNull($res->viewData('rows')->first(fn ($r) => $r['user']->full_name === 'Kỹ Thuật Không Làm Gì'));
        $this->assertSame(1, $res->viewData('totals')['unassigned']);
    }

    public function test_period_filter_excludes_old_activity(): void
    {
        $lead = Lead::factory()->create(['assigned_to' => $this->an->id, 'status' => 'consulting', 'created_at' => '2026-09-01']);
        $lead->log('Gọi tháng 9', 'call', $this->an->id)->forceFill(['created_at' => '2026-09-02'])->save();

        $res = $this->actingAs($this->an)->get(self::CRM.'/reports/consultants?period=this_month');
        $this->assertSame(0, $this->row('An Tư Vấn', $res)['activities_total']);

        $res = $this->get(self::CRM.'/reports/consultants?period=this_year');
        $this->assertSame(1, $this->row('An Tư Vấn', $res)['activities_total']);
    }
}
