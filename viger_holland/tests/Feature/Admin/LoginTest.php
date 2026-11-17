<?php

namespace Tests\Feature\Admin;

use App\Filament\Auth\Login;
use App\Models\User;
use App\Modules\Access\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Đăng nhập khu quản trị (Filament): tên đăng nhập hoặc email, thông báo tiếng Việt, khóa tạm khi sai nhiều,
 * tài khoản bị khóa không vào được, bị khóa giữa phiên thì bị đăng xuất.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_is_redirected_to_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
        $this->get(route('admin.media.index'))->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_login_page_renders_in_vietnamese_and_is_noindex(): void
    {
        $this->get(route('filament.admin.auth.login'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Đăng nhập quản trị')
            ->assertSee('Tên đăng nhập hoặc email');
    }

    public function test_admin_can_log_in_with_username(): void
    {
        $user = User::factory()->create(['username' => 'SPN', 'password' => 'mat-khau-dung']);

        Livewire::test(Login::class)
            ->fillForm(['login' => 'SPN', 'password' => 'mat-khau-dung'])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect(route('filament.admin.pages.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_admin_can_log_in_with_email(): void
    {
        $user = User::factory()->create(['email' => 'quantri@example.com', 'password' => 'mat-khau-dung']);

        Livewire::test(Login::class)
            ->fillForm(['login' => 'quantri@example.com', 'password' => 'mat-khau-dung'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_shows_vietnamese_error(): void
    {
        User::factory()->create(['username' => 'SPN', 'password' => 'mat-khau-dung']);

        Livewire::test(Login::class)
            ->fillForm(['login' => 'SPN', 'password' => 'sai'])
            ->call('authenticate')
            ->assertHasFormErrors(['login'])
            ->assertSee('Tên đăng nhập hoặc mật khẩu không đúng');

        $this->assertGuest();
    }

    public function test_empty_form_shows_required_errors(): void
    {
        Livewire::test(Login::class)
            ->fillForm(['login' => '', 'password' => ''])
            ->call('authenticate')
            ->assertHasFormErrors(['login' => 'required', 'password' => 'required']);
    }

    public function test_inactive_account_or_disabled_role_cannot_log_in(): void
    {
        User::factory()->inactive()->create(['username' => 'nghiviec', 'password' => 'mat-khau-dung']);
        User::factory()->create(['username' => 'tuvan', 'password' => 'mat-khau-dung', 'role' => User::ROLE_COUNSELOR]);
        Role::where('code', User::ROLE_COUNSELOR)->update(['is_active' => false]);

        foreach (['nghiviec', 'tuvan'] as $username) {
            Livewire::test(Login::class)
                ->fillForm(['login' => $username, 'password' => 'mat-khau-dung'])
                ->call('authenticate')
                ->assertHasFormErrors(['login']);
        }

        $this->assertGuest();
    }

    public function test_account_locked_during_session_is_logged_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get(route('filament.admin.pages.dashboard'))->assertRedirect(route('filament.admin.auth.login'));
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        User::factory()->create(['username' => 'SPN', 'password' => 'mat-khau-dung']);

        $component = Livewire::test(Login::class);
        for ($i = 0; $i < 5; $i++) {
            $component->fillForm(['login' => 'SPN', 'password' => 'sai'])->call('authenticate');
        }

        $component->fillForm(['login' => 'SPN', 'password' => 'mat-khau-dung'])->call('authenticate');

        $this->assertGuest();
    }

    public function test_admin_can_log_out(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('filament.admin.auth.logout'))->assertRedirect();

        $this->assertGuest();
    }
}
