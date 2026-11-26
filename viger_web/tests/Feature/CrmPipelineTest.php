<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmPipelineTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);
    }

    public function test_board_groups_leads_by_status(): void
    {
        Lead::factory()->create(['full_name' => 'Khách Mới', 'status' => 'new']);
        Lead::factory()->count(2)->create(['status' => 'consulting']);

        $res = $this->actingAs($this->user)->get(self::CRM.'/pipeline')->assertOk()
            ->assertSee('Quy trình tư vấn')
            ->assertSee('data-status="new"', false)
            ->assertSee('Khách Mới');

        $columns = $res->viewData('columns');
        $this->assertSame(array_keys(Lead::STATUSES), $columns->keys()->all());
        $this->assertSame(1, $columns['new']['total']);
        $this->assertSame(2, $columns['consulting']['total']);
        $this->assertSame(0, $columns['potential']['total']);
    }

    public function test_closed_columns_only_show_recent(): void
    {
        Lead::factory()->create(['full_name' => 'Chốt Gần', 'status' => 'converted']);
        $old = Lead::factory()->create(['full_name' => 'Chốt Xưa', 'status' => 'converted']);
        Lead::whereKey($old->id)->update(['updated_at' => now()->subDays(60)]);

        $columns = $this->actingAs($this->user)->get(self::CRM.'/pipeline')->viewData('columns');

        $this->assertSame(['Chốt Gần'], $columns['converted']['leads']->pluck('full_name')->all());
    }

    public function test_move_via_json_changes_status_and_logs_history(): void
    {
        $lead = Lead::factory()->create(['status' => 'new']);

        $this->actingAs($this->user)
            ->patchJson(self::CRM."/pipeline/{$lead->id}", ['status' => 'consulting'])
            ->assertOk()
            ->assertJson(['changed' => true, 'status' => 'consulting', 'label' => 'Đang tư vấn']);

        $this->assertSame('consulting', $lead->fresh()->status);
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'system',
            'content' => 'Đổi trạng thái: Mới → Đang tư vấn.',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_move_same_status_does_not_log(): void
    {
        $lead = Lead::factory()->create(['status' => 'new']);
        $before = $lead->activities()->count();

        $this->actingAs($this->user)
            ->patchJson(self::CRM."/pipeline/{$lead->id}", ['status' => 'new'])
            ->assertJson(['changed' => false]);

        $this->assertSame($before, $lead->activities()->count());
    }

    public function test_move_via_form_fallback_redirects_back(): void
    {
        $lead = Lead::factory()->create(['status' => 'potential', 'full_name' => 'Phụ Huynh B']);

        $this->actingAs($this->user)
            ->from(self::CRM.'/pipeline')
            ->patch(self::CRM."/pipeline/{$lead->id}", ['status' => 'converted'])
            ->assertRedirect(self::CRM.'/pipeline')
            ->assertSessionHas('success');

        // Ngày chốt hiện đúng ở trang Đã chuyển đổi (đọc dòng lịch sử)
        $this->get(self::CRM.'/converted')->assertSee('Phụ Huynh B')->assertDontSee('ước tính');
    }

    public function test_move_rejects_invalid_status_and_guest(): void
    {
        $lead = Lead::factory()->create(['status' => 'new']);

        $this->patchJson(self::CRM."/pipeline/{$lead->id}", ['status' => 'consulting'])->assertUnauthorized();

        $this->actingAs($this->user)
            ->patchJson(self::CRM."/pipeline/{$lead->id}", ['status' => 'hacked'])
            ->assertUnprocessable();

        $this->assertSame('new', $lead->fresh()->status);
    }

    public function test_filter_my_leads(): void
    {
        Lead::factory()->create(['full_name' => 'Của Tôi', 'assigned_to' => $this->user->id, 'status' => 'new']);
        Lead::factory()->create(['full_name' => 'Người Khác', 'status' => 'new']);

        $columns = $this->actingAs($this->user)
            ->get(self::CRM.'/pipeline?assigned_to='.$this->user->id)
            ->viewData('columns');

        $this->assertSame(['Của Tôi'], $columns['new']['leads']->pluck('full_name')->all());
    }
}
