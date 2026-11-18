<?php

namespace Tests\Feature\Respondent;

use App\Filament\Pages\ActivityLog;
use App\Models\AdminActivity;
use App\Models\User;
use App\Modules\Assessment\Filament\Pages\TestStats;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Services\AssessmentService;
use App\Modules\Respondent\Filament\Pages\CustomersList;
use App\Modules\Respondent\Services\ReportAccessService;
use App\Modules\Respondent\Services\ReportForm;
use App\Modules\Respondent\Support\InternalReport;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * H6 – trang chỉ quản trị viên: Danh sách khách đã làm bài (tìm, lọc, tải Excel có ghi lịch sử), Số liệu làm bài, Lịch sử thao tác.
 */
class AdminDataPagesTest extends TestCase
{
    use RefreshDatabase;

    private const HS01 = [
        'I' => [5, 5, 5, 5, 4, 4], 'S' => [5, 5, 4, 4, 4, 4], 'A' => [5, 4, 4, 4, 4, 4],
        'R' => [4, 4, 3, 3, 3, 3], 'C' => [4, 3, 3, 3, 3, 3], 'E' => [3, 3, 2, 2, 2, 2],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_sees_every_customer_searches_exactly_and_exports_with_a_log(): void
    {
        $anh = $this->customer('Nguyễn Minh Anh', 'minhanh@gmail.com', '0912345678', 'facebook');
        $this->customer('Trần Thu Hà', 'thuha@gmail.com', '0903111222', null);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->get(CustomersList::getUrl())->assertOk()
            ->assertSee('Nguyễn Minh Anh')->assertSee('Trần Thu Hà')->assertSee('ISA')->assertSee(InternalReport::code($anh))
            ->assertSee('Tải ra Excel');

        Livewire::test(CustomersList::class)->searchTable('0912 345 678')->assertCanSeeTableRecords([$anh])->assertCountTableRecords(1);
        Livewire::test(CustomersList::class)->searchTable('MinhAnh@gmail.com')->assertCountTableRecords(1);
        Livewire::test(CustomersList::class)->searchTable('Nguyễn')->assertCountTableRecords(0);
        Livewire::test(CustomersList::class)->filterTable('nguon', 'facebook')->assertCanSeeTableRecords([$anh])->assertCountTableRecords(1);

        Livewire::test(CustomersList::class)->callAction('export')->assertFileDownloaded();
        $this->assertTrue(AdminActivity::query()->where('action', 'export')->where('description', 'Tải danh sách khách ra Excel – 2 dòng')->exists());
    }

    public function test_stats_show_the_funnel_without_customer_names(): void
    {
        $this->customer('Nguyễn Minh Anh', 'minhanh@gmail.com', '0912345678', 'facebook');
        app(AssessmentService::class)->start(null, null);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->get(TestStats::getUrl())->assertOk()
            ->assertSeeInOrder(['Bắt đầu làm bài', '2', 'Làm xong bài', '1', 'Nhập đúng mã, xem báo cáo', '1'])
            ->assertSee('50% người bắt đầu làm bài')
            ->assertSee('IS – Nghiên cứu + Xã hội')->assertSee('facebook')->assertSee('Tâm lý học')
            ->assertDontSee('Nguyễn Minh Anh')->assertDontSee('0912345678');

        Livewire::test(TestStats::class)->call('setPeriod', '7')->assertSet('period', '7');
    }

    public function test_activity_log_is_read_only_and_shows_who_did_what(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Quản trị SPN']);
        $this->actingAs($admin);
        AdminActivity::record(AdminActivity::ACTION_SEARCH, 'respondents', null, 'Tra cứu báo cáo khách theo số điện thoại – 1 kết quả');

        $this->get(ActivityLog::getUrl())->assertOk()
            ->assertSee('Quản trị SPN')->assertSee('Tra cứu')->assertSee('theo số điện thoại – 1 kết quả');
        Livewire::test(ActivityLog::class)->assertTableActionDoesNotExist('delete')->assertTableActionDoesNotExist('edit');
    }

    public function test_counselor_and_staff_cannot_reach_admin_only_pages(): void
    {
        foreach ([User::ROLE_STAFF, User::ROLE_COUNSELOR] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            foreach ([CustomersList::getUrl(), TestStats::getUrl(), ActivityLog::getUrl()] as $url) {
                $this->get($url)->assertNotFound();
            }
        }
    }

    private function customer(string $name, string $email, string $parentPhone, ?string $source): TestSession
    {
        $service = app(AssessmentService::class);
        $session = $service->start(null, $source ? ['utm_source' => $source] : null);
        $next = array_map(fn () => 0, self::HS01);
        $answers = [];
        foreach ($session->sessionQuestions()->get() as $item) {
            $answers[$item->question_id] = ['value' => self::HS01[$item->group][$next[$item->group]++], 'ms' => 4000];
        }
        $session = $service->finish($session, $answers);
        app(ReportForm::class)->save($session, ['ho_ten' => $name, 'ngay_sinh' => '2009-03-15', 'email' => $email, 'sdt_phu_huynh' => $parentPhone, 'doi_tuong' => 'HS']);
        app(ReportAccessService::class)->complete($session, null);

        return $session->fresh();
    }
}
