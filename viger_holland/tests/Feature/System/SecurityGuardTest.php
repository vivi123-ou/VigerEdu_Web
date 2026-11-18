<?php

namespace Tests\Feature\System;

use App\Models\AdminActivity;
use App\Models\Setting;
use App\Models\User;
use App\Modules\System\Filament\Pages\BlockedIps;
use App\Modules\System\Models\BlockedIp;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ý Thầy 18/11 – chống tấn công web PHP: dò đường hacker hay dò → 404 và tự chặn; quá nhiều yêu cầu / phút → 429 rồi chặn;
 * IP văn phòng không bị chặn; quản trị viên xem / bỏ chặn / chặn tay; header bảo mật; dọn dòng chặn hết hạn.
 */
class SecurityGuardTest extends TestCase
{
    use RefreshDatabase;

    private const ATTACKER = '203.0.113.9';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_probing_hacker_paths_returns_404_and_blocks_the_ip(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::ATTACKER]);

        $this->get('/wp-admin/install.php')->assertNotFound()->assertSee('Không tìm thấy.');
        $this->get('/.env')->assertNotFound();

        $this->get(route('home'))->assertForbidden()->assertSee('tạm thời bị chặn');
        $blocked = BlockedIp::query()->where('ip', self::ATTACKER)->sole();
        $this->assertStringContainsString('Dò đường', $blocked->reason);
        $this->assertTrue($blocked->blocked_until->isFuture());
        $this->assertTrue(AdminActivity::where('description', 'like', 'Hệ thống tự chặn IP '.self::ATTACKER.'%')->exists());

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7']);
        $this->get(route('home'))->assertOk();
    }

    public function test_too_many_requests_get_429_and_office_ip_is_never_limited(): void
    {
        Setting::put('security_requests_per_minute', '30', 'system');
        $this->withServerVariables(['REMOTE_ADDR' => self::ATTACKER]);

        for ($i = 0; $i < 30; $i++) {
            $this->get('/up')->assertOk();
        }
        $this->get('/up')->assertStatus(429)->assertHeader('Retry-After', '60');

        Setting::put('security_allowlist', "113.161.10.0/24\n", 'system');
        $this->withServerVariables(['REMOTE_ADDR' => '113.161.10.20']);
        for ($i = 0; $i < 35; $i++) {
            $this->get('/up')->assertOk();
        }
        $this->get('/wp-login.php')->assertNotFound();
        $this->get('/up')->assertOk();
        $this->assertFalse(BlockedIp::query()->where('ip', '113.161.10.20')->exists());
    }

    public function test_admin_blocks_and_unblocks_ips_and_others_cannot_see_the_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        Livewire::test(BlockedIps::class)
            ->callAction('block', data: ['ip' => self::ATTACKER, 'reason' => 'Spam form', 'minutes' => 0])
            ->assertHasNoActionErrors();
        $blocked = BlockedIp::query()->where('ip', self::ATTACKER)->sole();
        $this->assertNull($blocked->blocked_until, '0 phút = chặn đến khi bỏ chặn');

        Livewire::test(BlockedIps::class)->callAction('block', data: ['ip' => 'abc', 'reason' => 'x', 'minutes' => 5])->assertHasActionErrors(['ip']);
        Livewire::test(BlockedIps::class)->callAction('block', data: ['ip' => '127.0.0.1', 'reason' => 'x', 'minutes' => 5])->assertHasActionErrors(['ip']);

        $this->withServerVariables(['REMOTE_ADDR' => self::ATTACKER])->get(route('home'))->assertForbidden();

        Livewire::test(BlockedIps::class)->assertCanSeeTableRecords([$blocked])->callTableAction('unblock', $blocked);
        $this->assertModelMissing($blocked);
        $this->withServerVariables(['REMOTE_ADDR' => self::ATTACKER])->get(route('home'))->assertOk();

        foreach ([User::ROLE_STAFF, User::ROLE_COUNSELOR] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->get(BlockedIps::getUrl())->assertNotFound();
        }
    }

    public function test_security_headers_are_sent_and_embed_stays_frameable(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('X-Powered-By');

        $this->get(route('embed'))->assertHeaderMissing('X-Frame-Options')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_expired_blocks_are_pruned_and_permanent_ones_kept(): void
    {
        $expired = BlockedIp::query()->create(['ip' => '192.0.2.1', 'reason' => 'x', 'blocked_until' => now()->subDays(2)]);
        $permanent = BlockedIp::query()->create(['ip' => '192.0.2.2', 'reason' => 'x', 'blocked_until' => null]);

        $this->artisan('holland:prune')->assertSuccessful();

        $this->assertModelMissing($expired);
        $this->assertModelExists($permanent);
    }
}
