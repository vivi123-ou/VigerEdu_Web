<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_login_page_renders_in_vietnamese_and_is_noindex(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Đăng nhập quản trị')
            ->assertSee('noindex, nofollow', false);
    }

    public function test_admin_can_log_in_with_username(): void
    {
        $user = User::factory()->create(['username' => 'SPN', 'password' => 'mat-khau-dung']);

        $this->post(route('admin.login.store'), ['username' => 'SPN', 'password' => 'mat-khau-dung'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_admin_can_log_in_with_email(): void
    {
        $user = User::factory()->create(['email' => 'quantri@example.com', 'password' => 'mat-khau-dung']);

        $this->post(route('admin.login.store'), ['username' => 'quantri@example.com', 'password' => 'mat-khau-dung'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_shows_vietnamese_error(): void
    {
        User::factory()->create(['username' => 'SPN', 'password' => 'mat-khau-dung']);

        $this->from(route('admin.login'))
            ->post(route('admin.login.store'), ['username' => 'SPN', 'password' => 'sai'])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors(['username' => 'Tên đăng nhập hoặc mật khẩu không đúng.']);

        $this->assertGuest();
    }

    public function test_empty_form_shows_required_errors(): void
    {
        $this->post(route('admin.login.store'), [])
            ->assertSessionHasErrors(['username' => 'Vui lòng nhập tên đăng nhập.', 'password' => 'Vui lòng nhập mật khẩu.']);
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        User::factory()->inactive()->create(['username' => 'nghiviec', 'password' => 'mat-khau-dung']);

        $this->post(route('admin.login.store'), ['username' => 'nghiviec', 'password' => 'mat-khau-dung'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_account_locked_during_session_is_logged_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        RateLimiter::clear('spn|127.0.0.1');
        User::factory()->create(['username' => 'SPN', 'password' => 'mat-khau-dung']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.store'), ['username' => 'SPN', 'password' => 'sai']);
        }

        $this->post(route('admin.login.store'), ['username' => 'SPN', 'password' => 'mat-khau-dung'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_admin_can_log_out(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }
}
