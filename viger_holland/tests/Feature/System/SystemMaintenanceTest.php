<?php

namespace Tests\Feature\System;

use App\Filament\Pages\ActivityLog;
use App\Models\AdminActivity;
use App\Models\User;
use App\Modules\Assessment\Services\AssessmentService;
use App\Modules\CrmSync\Models\CrmOutbox;
use App\Modules\System\Filament\Pages\SystemSettings;
use App\Modules\Tracking\Models\TrackingEvent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ý Thầy 18/11: nhật ký ghi liên tục nhưng tự dọn sau một thời gian (mặc định 12 tháng) để không tràn dữ liệu;
 * số tháng / ngày giữ sửa ở "Cài đặt hệ thống" (chỉ quản trị viên). Dữ liệu khách không bao giờ tự xóa.
 */
class SystemMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_prune_removes_only_old_logs_and_keeps_customer_data(): void
    {
        $session = app(AssessmentService::class)->start(null, null);

        $oldLog = AdminActivity::record(AdminActivity::ACTION_UPDATE, 'settings.intro', null, 'cũ');
        $oldLog->forceFill(['created_at' => now()->subMonths(13)])->save();
        $newLog = AdminActivity::record(AdminActivity::ACTION_UPDATE, 'settings.intro', null, 'mới');
        $newLog->forceFill(['created_at' => now()->subMonths(11)])->save();

        $oldEvent = TrackingEvent::create(['test_session_id' => $session->id, 'type' => 'cta_call', 'occurred_at' => now()->subMonths(13)]);
        $newEvent = TrackingEvent::create(['test_session_id' => $session->id, 'type' => 'cta_call', 'occurred_at' => now()->subMonth()]);

        $outbox = fn (string $status, $sentAt) => CrmOutbox::create(['event_id' => (string) Str::uuid(), 'test_session_id' => $session->id,
            'schema' => 'lead/2', 'payload' => [], 'status' => $status, 'tries' => 1, 'sent_at' => $sentAt]);
        $sentOld = $outbox('sent', now()->subDays(100));
        $sentNew = $outbox('sent', now()->subDays(10));
        $failedOld = $outbox('failed', null);
        $failedOld->forceFill(['created_at' => now()->subYears(2)])->save();

        $this->artisan('holland:prune --dry-run')->expectsOutputToContain('Sẽ xóa')->assertSuccessful();
        $this->assertModelExists($oldLog);

        $this->artisan('holland:prune')->assertSuccessful();

        $this->assertModelMissing($oldLog);
        $this->assertModelExists($newLog);
        $this->assertModelMissing($oldEvent);
        $this->assertModelExists($newEvent);
        $this->assertModelMissing($sentOld);
        $this->assertModelExists($sentNew);
        $this->assertModelExists($failedOld, 'Gói lỗi chưa gửi được không bị xóa');
        $this->assertModelExists($session, 'Lượt làm bài của khách không bị xóa');
        $this->assertTrue(AdminActivity::where('description', 'like', 'Hệ thống tự dọn nhật ký cũ%')->exists());
    }

    public function test_keep_period_follows_system_settings(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $log = AdminActivity::record(AdminActivity::ACTION_UPDATE, 'settings.intro', null, 'x');
        $log->forceFill(['created_at' => now()->subMonths(4)])->save();

        Livewire::test(SystemSettings::class)
            ->fillForm(['logs_keep_months' => 0])->call('save')
            ->assertHasFormErrors(['logs_keep_months' => 'Ô "Lịch sử thao tác giữ" nhỏ nhất là 1.']);

        Livewire::test(SystemSettings::class)->fillForm(['logs_keep_months' => 3])->call('save')->assertHasNoFormErrors();
        $this->assertSame('3', setting('logs_keep_months'));
        $this->get(ActivityLog::getUrl())->assertSee('tự xóa dòng cũ hơn 3 tháng');

        $this->artisan('holland:prune')->assertSuccessful();
        $this->assertModelMissing($log);
    }

    public function test_only_admin_reaches_system_settings(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->get(SystemSettings::getUrl())->assertOk()->assertSee('Nhật ký tự dọn');

        foreach ([User::ROLE_STAFF, User::ROLE_COUNSELOR] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(SystemSettings::getUrl())->assertNotFound();
        }
    }

    public function test_prune_is_scheduled_nightly(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('holland:prune')->assertSuccessful();
        $this->assertSame(0, DB::table('admin_activities')->where('description', 'like', 'Hệ thống tự dọn%')->count());
    }
}
