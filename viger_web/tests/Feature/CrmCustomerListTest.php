<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmCustomerListTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);
    }

    /** Chỉ xét tên trong bảng (tên khách còn có thể xuất hiện trong chuông thông báo) */
    private function row(string $name): string
    {
        return 'class="link-strong">'.$name.'</a>';
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(self::CRM.'/customers')->assertRedirect(self::CRM.'/login');
    }

    public function test_lists_all_statuses_with_tab_counts(): void
    {
        Lead::factory()->create(['full_name' => 'Khách Mới', 'status' => 'new']);
        Lead::factory()->create(['full_name' => 'Khách Chốt', 'status' => 'converted']);
        Lead::factory()->create(['full_name' => 'Khách Loại', 'status' => 'lost']);

        $res = $this->actingAs($this->user)->get(self::CRM.'/customers')->assertOk()
            ->assertSee($this->row('Khách Mới'), false)
            ->assertSee($this->row('Khách Chốt'), false)
            ->assertSee($this->row('Khách Loại'), false);

        $this->assertSame(['all' => 3, 'open' => 1, 'converted' => 1, 'lost' => 1], $res->viewData('counts')->all());
    }

    public function test_tabs_filter_by_status_group(): void
    {
        Lead::factory()->create(['full_name' => 'Khách Mới', 'status' => 'new']);
        Lead::factory()->create(['full_name' => 'Khách Chốt', 'status' => 'converted']);

        $this->actingAs($this->user)->get(self::CRM.'/customers?tab=converted')
            ->assertSee($this->row('Khách Chốt'), false)
            ->assertDontSee($this->row('Khách Mới'), false);

        $this->get(self::CRM.'/customers?tab=open')
            ->assertSee($this->row('Khách Mới'), false)
            ->assertDontSee($this->row('Khách Chốt'), false);
    }

    public function test_search_and_sort_by_last_activity(): void
    {
        $old = Lead::factory()->create(['full_name' => 'Lâu Rồi', 'student_name' => 'Bé Na']);
        $recent = Lead::factory()->create(['full_name' => 'Vừa Gọi']);
        $old->activities()->update(['created_at' => now()->subDays(30)]);
        $recent->log('Gọi điện', 'call', $this->user->id);

        $this->actingAs($this->user);

        // Tìm theo tên học sinh
        $this->get(self::CRM.'/customers?q=Bé Na')
            ->assertSee($this->row('Lâu Rồi'), false)
            ->assertDontSee($this->row('Vừa Gọi'), false);

        $this->get(self::CRM.'/customers')->assertSeeInOrder([$this->row('Vừa Gọi'), $this->row('Lâu Rồi')], false);
        $this->get(self::CRM.'/customers?sort=idle')->assertSeeInOrder([$this->row('Lâu Rồi'), $this->row('Vừa Gọi')], false);
    }

    public function test_paginates_20_per_page(): void
    {
        Lead::factory()->count(25)->create();

        $res = $this->actingAs($this->user)->get(self::CRM.'/customers')->assertOk();
        $this->assertCount(20, $res->viewData('customers'));
        $res->assertSee('aria-label="Trang sau"', false);
    }

    public function test_menu_item_is_active(): void
    {
        $this->actingAs($this->user)->get(self::CRM.'/customers')
            ->assertSee('href="'.self::CRM.'/customers"', false)
            ->assertSee('Quản lý khách hàng');
    }
}
