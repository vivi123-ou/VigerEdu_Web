<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    public function test_admin_sees_activities_by_others_but_not_own(): void
    {
        $admin = User::factory()->create(['active' => true, 'role' => 'ADMIN']);
        $staff = User::factory()->create(['active' => true, 'role' => 'USER', 'full_name' => 'NV Gọi Điện']);

        $lead = Lead::factory()->create(['full_name' => 'Phụ Huynh Một']);  // log hệ thống (user_id null)
        $lead->log('Gọi cho phụ huynh lần 1', 'call', $staff->id);
        $lead->log('Ghi chú của chính admin', 'note', $admin->id);

        $this->actingAs($admin)->get(self::CRM.'/leads')
            ->assertOk()
            ->assertSee('Gọi cho phụ huynh lần 1')
            ->assertSee('NV Gọi Điện')
            ->assertSee('2 mới')
            ->assertDontSee('Ghi chú của chính admin');
    }

    public function test_staff_only_sees_assigned_leads(): void
    {
        $staff = User::factory()->create(['active' => true, 'role' => 'USER']);
        $other = User::factory()->create(['active' => true, 'role' => 'USER']);

        Lead::factory()->create(['full_name' => 'Khách Của Tôi', 'assigned_to' => $staff->id]);
        Lead::factory()->create(['full_name' => 'Khách Người Khác', 'assigned_to' => $other->id]);

        $this->actingAs($staff)->get(self::CRM.'/leads')
            ->assertSee('crm-notice', false)
            ->assertSeeInOrder(['Thông báo', 'Khách Của Tôi'])
            ->assertDontSee('Khách Người Khác</b>', false);
    }

    public function test_mark_read_resets_unread_counter(): void
    {
        $admin = User::factory()->create(['active' => true, 'role' => 'ADMIN']);
        Lead::factory()->create();

        $this->actingAs($admin)->get(self::CRM.'/')->assertSee('class="crm-badge"', false);

        $this->postJson(self::CRM.'/notifications/read')->assertOk()->assertJson(['unread' => 0]);

        $this->travel(1)->seconds();
        $this->get(self::CRM.'/')->assertDontSee('class="crm-badge"', false);
    }

    public function test_empty_state(): void
    {
        $this->actingAs(User::factory()->create(['crm_role' => 'admin', 'active' => true]))
            ->get(self::CRM.'/')
            ->assertSee('Chưa có thông báo nào.');
    }
}
