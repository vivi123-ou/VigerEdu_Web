<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmAppointmentTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $user;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['crm_role' => 'admin', 'active' => true, 'full_name' => 'Tư Vấn A']);
        $this->lead = Lead::factory()->create(['full_name' => 'Phụ Huynh X', 'assigned_to' => $this->user->id]);
    }

    private function book(array $override = [])
    {
        return $this->actingAs($this->user)->post(self::CRM.'/appointments', array_merge([
            'lead_id' => $this->lead->id,
            'user_id' => $this->user->id,
            'scheduled_at' => now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i'),
            'duration_minutes' => 30,
            'type' => 'office',
            'note' => 'Tư vấn Ausbildung',
        ], $override));
    }

    private function appt(array $attrs = []): Appointment
    {
        return Appointment::create(array_merge([
            'lead_id' => $this->lead->id,
            'user_id' => $this->user->id,
            'scheduled_at' => now()->addDay()->setTime(9, 0),
            'duration_minutes' => 30,
            'type' => 'call',
            'status' => 'scheduled',
        ], $attrs));
    }

    public function test_book_appointment_and_log_history(): void
    {
        $this->book()->assertRedirect(self::CRM.'/appointments')->assertSessionHas('success');

        $a = Appointment::sole();
        $this->assertSame('scheduled', $a->status);
        $this->assertSame($this->user->id, $a->created_by);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $this->lead->id,
            'type' => 'appointment',
            'content' => 'Đặt lịch hẹn: Gặp tại văn phòng lúc '.$a->scheduled_at->format('H:i d/m/Y').' (phụ trách: Tư Vấn A). Nội dung: Tư vấn Ausbildung',
        ]);
    }

    public function test_book_from_lead_profile_returns_to_profile(): void
    {
        $this->actingAs($this->user)->get(self::CRM."/appointments/create?lead={$this->lead->id}&return=lead")
            ->assertOk()->assertSee('Phụ Huynh X');

        $this->book(['return' => 'lead'])->assertRedirect(self::CRM."/leads/{$this->lead->id}#appointments");

        $this->get(self::CRM."/leads/{$this->lead->id}")->assertSee('Gặp tại văn phòng')->assertSee('Đổi lịch');
    }

    public function test_validation_past_time_and_overlap(): void
    {
        $this->book(['scheduled_at' => now()->subHour()->format('Y-m-d\TH:i')])->assertSessionHasErrors('scheduled_at');
        $this->book(['type' => 'x', 'lead_id' => 999999])->assertSessionHasErrors(['type', 'lead_id']);

        $this->book();  // 09:00 – 09:30
        $other = Lead::factory()->create();

        // 09:15 chồng giờ cùng tư vấn viên => lỗi
        $this->book(['lead_id' => $other->id, 'scheduled_at' => now()->addDay()->setTime(9, 15)->format('Y-m-d\TH:i')])
            ->assertSessionHasErrors('scheduled_at');

        // 09:30 sát giờ (không chồng) => được
        $this->book(['lead_id' => $other->id, 'scheduled_at' => now()->addDay()->setTime(9, 30)->format('Y-m-d\TH:i')])
            ->assertSessionHasNoErrors();

        // Tư vấn viên khác cùng giờ => được
        $colleague = User::factory()->create(['crm_role' => 'admin', 'active' => true]);
        $this->book(['lead_id' => $other->id, 'user_id' => $colleague->id])->assertSessionHasNoErrors();

        $this->assertSame(3, Appointment::count());
    }

    public function test_reschedule_logs_change(): void
    {
        $a = $this->appt();
        $newTime = now()->addDays(2)->setTime(14, 0);

        $this->actingAs($this->user)->get(self::CRM."/appointments/{$a->id}/edit")->assertOk();

        $this->put(self::CRM."/appointments/{$a->id}", [
            'lead_id' => $this->lead->id, 'user_id' => $this->user->id,
            'scheduled_at' => $newTime->format('Y-m-d\TH:i'), 'duration_minutes' => 30, 'type' => 'call',
        ])->assertRedirect(self::CRM.'/appointments');

        $this->assertTrue($a->fresh()->scheduled_at->eq($newTime));
        $this->assertStringContainsString('Đổi lịch hẹn: Gọi điện lúc', $this->lead->activities()->pluck('content')->implode("\n"));
    }

    public function test_complete_with_result_and_block_double_update(): void
    {
        $a = $this->appt();

        $this->actingAs($this->user)->post(self::CRM."/appointments/{$a->id}/status", [
            'status' => 'done', 'result' => 'Khách đồng ý làm hồ sơ',
        ])->assertRedirect();

        $this->assertSame('done', $a->fresh()->status);
        $this->assertSame('Khách đồng ý làm hồ sơ', $a->fresh()->result);
        $this->assertStringContainsString('Hoàn thành lịch hẹn: Gọi điện lúc', $this->lead->activities()->pluck('content')->implode("\n"));

        // Đã cập nhật rồi thì không cập nhật lại / không đổi lịch được
        $this->post(self::CRM."/appointments/{$a->id}/status", ['status' => 'cancelled'])->assertStatus(422);
        $this->get(self::CRM."/appointments/{$a->id}/edit")->assertForbidden();
        $this->post(self::CRM."/appointments/{$a->id}/status", ['status' => 'scheduled'])->assertSessionHasErrors('status');
    }

    public function test_tabs_upcoming_today_overdue_done_cancelled(): void
    {
        $this->appt(['scheduled_at' => now()->addDays(3), 'note' => 'Lịch tuần sau']);
        $this->appt(['scheduled_at' => now()->subDay(), 'note' => 'Lịch quá hạn']);
        $this->appt(['scheduled_at' => now()->subDays(2), 'status' => 'done', 'note' => 'Lịch đã xong']);
        $this->appt(['scheduled_at' => now()->subDays(2), 'status' => 'no_show', 'note' => 'Lịch vắng mặt']);

        $res = $this->actingAs($this->user)->get(self::CRM.'/appointments')->assertOk()
            ->assertSee('Lịch tuần sau')->assertDontSee('Lịch quá hạn');

        $this->assertSame(['upcoming' => 1, 'today' => 0, 'overdue' => 1, 'done' => 1, 'cancelled' => 1], $res->viewData('counts')->all());

        $this->get(self::CRM.'/appointments?tab=overdue')->assertSee('Lịch quá hạn')->assertSee('Quá giờ, chưa cập nhật');
        $this->get(self::CRM.'/appointments?tab=done')->assertSee('Lịch đã xong')->assertDontSee('Lịch tuần sau');
        $this->get(self::CRM.'/appointments?tab=cancelled')->assertSee('Lịch vắng mặt');
    }

    public function test_filter_my_appointments(): void
    {
        $colleague = User::factory()->create(['crm_role' => 'admin', 'active' => true]);
        $this->appt(['note' => 'Của tôi']);
        $this->appt(['user_id' => $colleague->id, 'note' => 'Của đồng nghiệp']);

        $this->actingAs($this->user)->get(self::CRM.'/appointments?user='.$this->user->id)
            ->assertSee('Của tôi')->assertDontSee('Của đồng nghiệp');
    }

    public function test_dashboard_shows_upcoming_and_overdue(): void
    {
        $this->appt(['scheduled_at' => now()->addHours(3)]);
        $this->appt(['scheduled_at' => now()->subHours(3)]);

        $this->actingAs($this->user)->get(self::CRM.'/')
            ->assertOk()
            ->assertSee('Lịch hẹn sắp tới')
            ->assertSee('1</b> lịch hẹn quá giờ chưa cập nhật kết quả', false);
    }

    public function test_deleting_lead_removes_its_appointments(): void
    {
        $this->appt();
        $admin = User::factory()->create(['active' => true, 'role' => 'ADMIN']);

        $this->actingAs($admin)->delete(self::CRM."/leads/{$this->lead->id}");

        $this->assertSame(0, Appointment::count());
    }
}
