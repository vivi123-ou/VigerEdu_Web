<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRM chạy trên subdomain riêng — phpunit.xml đặt CRM_DOMAIN=crm.viger.test.
 */
class CrmDashboardTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    public function test_guest_is_redirected_to_crm_login(): void
    {
        $this->get(self::CRM.'/')->assertRedirect(self::CRM.'/login');
        $this->get(self::CRM.'/login')->assertOk()->assertSee('Viger CRM');
    }

    public function test_user_can_login_and_see_dashboard(): void
    {
        User::factory()->create(['crm_role' => 'admin',
            'username' => 'nv1',
            'password' => 'secret123',
            'active' => true,
            'full_name' => 'Nguyễn A',
        ]);

        $this->post(self::CRM.'/login', ['username' => 'nv1', 'password' => 'secret123'])
            ->assertRedirect(self::CRM);

        $this->get(self::CRM.'/')
            ->assertOk()
            ->assertSee('Xin chào')
            ->assertSee('Nguyễn A')
            ->assertSee('Khách hàng mới');
    }

    public function test_dashboard_shows_real_numbers(): void
    {
        $user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);

        Lead::factory()->count(3)->create(['status' => 'new', 'source' => 'website', 'assigned_to' => null]);
        Lead::factory()->count(2)->create(['status' => 'consulting', 'source' => 'holland', 'assigned_to' => $user->id]);
        Lead::factory()->create(['status' => 'converted', 'source' => 'chatbot', 'full_name' => 'Khách Chốt']);

        $response = $this->actingAs($user)->get(self::CRM.'/');
        $response->assertOk()
            ->assertSee('Khách Chốt')
            ->assertSee('Tỷ lệ chuyển đổi 16.7%');

        $view = $response->viewData('stats');
        $this->assertSame([6, 3, 2, 1], array_column($view, 'value'));
        $this->assertSame([3, 2, 1, 0], $response->viewData('sourceChart')['data']);
        $this->assertSame(6, array_sum($response->viewData('leadsChart')['data']));
        $this->assertSame([3, 3], array_slice(array_column($response->viewData('tasks'), 'count'), 0, 2));
    }

    public function test_dashboard_empty_state(): void
    {
        $this->actingAs(User::factory()->create(['crm_role' => 'admin', 'active' => true]))
            ->get(self::CRM.'/')
            ->assertOk()
            ->assertSee('Chưa có khách hàng nào');
    }

    public function test_wrong_password_shows_error(): void
    {
        User::factory()->create(['crm_role' => 'admin', 'username' => 'nv1', 'password' => 'secret123', 'active' => true]);

        $this->from(self::CRM.'/login')
            ->post(self::CRM.'/login', ['username' => 'nv1', 'password' => 'wrong'])
            ->assertRedirect(self::CRM.'/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_main_site_is_not_affected(): void
    {
        $this->get('http://localhost/login')->assertOk()->assertDontSee('Viger CRM');
        $this->get('http://localhost/crm')->assertNotFound();
    }
}
