<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('admin');
    }

    public function test_admin_logs_in_with_username(): void
    {
        $user = User::factory()->create(['username' => 'admin01', 'password' => 'secret123', 'role' => 'ADMIN', 'active' => true]);

        Livewire::test(Login::class)
            ->assertSee('Tên đăng nhập')
            ->fillForm(['username' => 'admin01', 'password' => 'secret123'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_or_inactive_user_is_rejected(): void
    {
        User::factory()->create(['username' => 'off01', 'password' => 'secret123', 'role' => 'ADMIN', 'active' => false]);

        Livewire::test(Login::class)
            ->fillForm(['username' => 'off01', 'password' => 'secret123'])
            ->call('authenticate')
            ->assertHasFormErrors(['username']);

        $this->assertGuest();
    }
}
