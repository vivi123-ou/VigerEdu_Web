<?php

namespace Tests\Feature\Respondent;

use App\Models\AdminActivity;
use App\Models\User;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Services\AssessmentService;
use App\Modules\Content\Filament\Pages\ReportTexts;
use App\Modules\Respondent\Filament\Pages\ContactsToCall;
use App\Modules\Respondent\Filament\Pages\InternalReportPage;
use App\Modules\Respondent\Filament\Pages\LookupReports;
use App\Modules\Respondent\Services\ReportAccessService;
use App\Modules\Respondent\Services\ReportForm;
use App\Modules\Respondent\Support\InternalReport;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * H4b – trang quản trị về khách: "Tra cứu báo cáo khách" + báo cáo nội bộ (tư vấn viên), "Khách cần liên lạc" (nhân viên),
 * "Chữ trên báo cáo & email". Đúng phân quyền chốt 17/11, không lộ giới hạn, có ghi nhật ký.
 */
class AdminCustomerPagesTest extends TestCase
{
    use RefreshDatabase;

    /** Hồ sơ HS01 của Thầy: mã đầy đủ ISA, khách thấy IS. */
    private const HS01 = [
        'I' => [5, 5, 5, 5, 4, 4], 'S' => [5, 5, 4, 4, 4, 4], 'A' => [5, 4, 4, 4, 4, 4],
        'R' => [4, 4, 3, 3, 3, 3], 'C' => [4, 3, 3, 3, 3, 3], 'E' => [3, 3, 2, 2, 2, 2],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_counselor_finds_one_customer_only_by_exact_phone_email_or_report_code(): void
    {
        $session = $this->customer('Nguyễn Minh Anh', 'minhanh@gmail.com', '0912345678', '0987654321');
        $this->customer('Trần Thu Hà', 'thuha@gmail.com', '0903111222');
        $this->actingAs($this->user(User::ROLE_COUNSELOR));

        foreach (['+84 912 345 678', '0987.654.321', 'MinhAnh@Gmail.com', InternalReport::code($session)] as $query) {
            Livewire::test(LookupReports::class)->set('search', $query)->call('find')
                ->assertSee('Nguyễn Minh Anh')->assertSee('ISA')->assertSee('Đọc báo cáo đầy đủ')
                ->assertDontSee('Trần Thu Hà');
        }

        // Gõ thiếu / gõ tên: không gợi ý khách nào.
        foreach (['0912345', 'Nguyễn Minh Anh', 'gmail.com'] as $query) {
            Livewire::test(LookupReports::class)->set('search', $query)->call('find')
                ->assertSee('Không tìm thấy khách')->assertDontSee('Nguyễn Minh Anh');
        }

        $logs = AdminActivity::query()->where('action', AdminActivity::ACTION_SEARCH)->pluck('description')->implode(' | ');
        $this->assertStringContainsString('Tra cứu báo cáo khách theo số điện thoại – 1 kết quả', $logs);
        $this->assertStringNotContainsString('0912', $logs, 'Nhật ký không ghi số điện thoại đã gõ');
        $this->assertStringNotContainsString('minhanh', $logs);
    }

    public function test_customer_with_two_attempts_shows_both_newest_first(): void
    {
        $this->customer('Nguyễn Minh Anh', 'minhanh@gmail.com', '0912345678', null, now()->subDays(30));
        $this->customer('Nguyễn Minh Anh', 'minhanh@gmail.com', '0912345678');
        $this->actingAs($this->user(User::ROLE_COUNSELOR));

        Livewire::test(LookupReports::class)->set('search', '0912345678')->call('find')
            ->assertSee('Khách đã làm bài 2 lần, lần mới nhất ở trên');
    }

    public function test_internal_report_for_counselor_has_everything_needed_but_no_print_or_ai_copy(): void
    {
        $session = $this->customer('Nguyễn Minh Anh', 'minhanh@gmail.com', '0912345678', '0987654321');
        $this->actingAs($this->user(User::ROLE_COUNSELOR));

        $this->get(InternalReportPage::getUrl(['ma' => $session->uuid]))->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Báo cáo của Nguyễn Minh Anh')
            ->assertSee('0912345678')->assertSee('0987654321')->assertSee('minhanh@gmail.com')
            ->assertSee('15/03/2009')
            ->assertSee('Đọc trước khi tư vấn')
            ->assertSeeInOrder(['Khách đang thấy', 'IS', 'Mã đầy đủ (nội bộ)', 'ISA'])
            ->assertSee('Vì sao khách chỉ thấy 2 chữ')
            ->assertSee('Nghiên cứu (4,67)')
            ->assertSee('Ngành gợi ý')->assertSee('Khách không thấy')
            ->assertSee('Nhà nghiên cứu tâm lý học (Psychologieforscher)')
            ->assertSee('36 câu khách đã trả lời')
            ->assertSee(InternalReport::code($session))
            ->assertDontSee('In / PDF')->assertDontSee('Sao chép cho AI');

        $this->assertSame(1, AdminActivity::query()->where('action', AdminActivity::ACTION_VIEW)
            ->where('subject_type', 'test_sessions')->where('subject_id', $session->id)->count());

        // Mã sai, lượt chưa nhập đúng mã email: không tìm thấy.
        $this->get(InternalReportPage::getUrl(['ma' => 'khong-co']))->assertNotFound();
        $unverified = app(AssessmentService::class)->start(null, null);
        $this->get(InternalReportPage::getUrl(['ma' => $unverified->uuid]))->assertNotFound();
    }

    public function test_admin_also_gets_print_and_an_ai_json_without_personal_data(): void
    {
        $session = $this->customer('Nguyễn Minh Anh', 'minhanh@gmail.com', '0912345678', '0987654321');
        $this->actingAs($this->user(User::ROLE_ADMIN));

        $page = $this->get(InternalReportPage::getUrl(['ma' => $session->uuid]))->assertOk()
            ->assertSee('In / PDF')->assertSee('Sao chép cho AI');

        $json = InternalReport::make($session->fresh())['aiJson'];
        $this->assertStringContainsString('"code3": "ISA"', $json);
        foreach (['Nguyễn', 'minhanh', '0912345678', '0987654321'] as $private) {
            $this->assertStringNotContainsString($private, $json);
        }
        $page->assertSee('&quot;code3&quot;: &quot;ISA&quot;', false);
    }

    public function test_staff_sees_recent_contacts_only_without_results_or_limits(): void
    {
        $anh = $this->customer('Nguyễn Minh Anh', 'minhanh@gmail.com', '0912345678', '0987654321');
        $this->customer('Trần Thu Hà', 'thuha@gmail.com', '0903111222', null, now()->subDay());
        $this->customer('Khách Cũ', 'cu@gmail.com', '0911000000', null, now()->subDays(10));
        $this->actingAs($this->user(User::ROLE_STAFF));

        $this->get(ContactsToCall::getUrl())->assertOk()
            ->assertSee('Mã báo cáo')->assertSee(InternalReport::code($anh))
            ->assertSee('Nguyễn Minh Anh')->assertSee('0912345678')->assertSee('0987654321')->assertSee('minhanh@gmail.com')
            ->assertSee('https://zalo.me/0912345678', false)
            ->assertSee('Trần Thu Hà')
            ->assertDontSee('Khách Cũ')
            ->assertDontSee('ISA')->assertDontSee('Nghiên cứu')->assertDontSee('Ngành')
            ->assertDontSee('7 ngày')->assertDontSee('Excel')->assertDontSee('ghi lại');

        Livewire::test(ContactsToCall::class)->set('search', 'nguyen minh')->assertSee('Nguyễn Minh Anh')->assertDontSee('Trần Thu Hà');
        Livewire::test(ContactsToCall::class)->set('search', '903111')->assertSee('Trần Thu Hà')->assertDontSee('Nguyễn Minh Anh');
        Livewire::test(ContactsToCall::class)->call('setTab', 'hom-qua')->assertSee('Trần Thu Hà')->assertDontSee('Nguyễn Minh Anh');

        $this->assertTrue(AdminActivity::query()->where('description', 'Mở danh sách khách cần liên lạc')->exists());
    }

    public function test_each_role_only_reaches_its_own_pages(): void
    {
        $session = $this->customer('Nguyễn Minh Anh', 'minhanh@gmail.com', '0912345678');

        $this->actingAs($this->user(User::ROLE_STAFF));
        $this->get(LookupReports::getUrl())->assertNotFound();
        $this->get(InternalReportPage::getUrl(['ma' => $session->uuid]))->assertNotFound();
        $this->get(ReportTexts::getUrl())->assertOk();

        $this->actingAs($this->user(User::ROLE_COUNSELOR));
        $this->get(ContactsToCall::getUrl())->assertNotFound();
        $this->get(ReportTexts::getUrl())->assertNotFound();
        $this->get(LookupReports::getUrl())->assertOk()->assertDontSee('30 lần')->assertDontSee('ghi lại');
    }

    public function test_report_texts_page_changes_what_the_customer_reads(): void
    {
        $this->actingAs($this->user(User::ROLE_STAFF));

        $this->get(ReportTexts::getUrl())->assertOk()
            ->assertSeeInOrder(['Trang làm bài', 'Kết quả sơ lược', 'Form thông tin', 'Mã xác nhận', 'Email gửi mã', 'Báo cáo đầy đủ', 'Báo cáo đã đóng'])
            ->assertDontSee('Xem trước');

        Livewire::test(ReportTexts::class)
            ->fillForm(['report_kicker' => 'Bản đồ sở thích của bạn', 'quiz_next_text' => 'Trang sau'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Bản đồ sở thích của bạn', setting('report_kicker'));
        $this->assertSame('Trang sau', setting('quiz_next_text'));
    }

    /**
     * Một khách đã làm xong bài, điền form và nhập đúng mã email (không qua trang khách cho nhanh).
     */
    private function customer(string $name, string $email, string $parentPhone, ?string $phone = null, $at = null): TestSession
    {
        $service = app(AssessmentService::class);
        $session = $service->start(null, null);
        $next = array_map(fn () => 0, self::HS01);
        $answers = [];
        foreach ($session->sessionQuestions()->get() as $item) {
            $answers[$item->question_id] = ['value' => self::HS01[$item->group][$next[$item->group]++], 'ms' => 4200];
        }
        $session = $service->finish($session, $answers);

        app(ReportForm::class)->save($session, array_filter([
            'ho_ten' => $name, 'ngay_sinh' => '2009-03-15', 'email' => $email, 'sdt_phu_huynh' => $parentPhone,
            'so_dien_thoai' => $phone, 'tinh' => 'TP. Hồ Chí Minh', 'doi_tuong' => 'HS',
        ]));
        app(ReportAccessService::class)->complete($session, null);

        if ($at !== null) {
            $session->forceFill(['verified_at' => $at, 'answered_at' => $at])->save();
        }

        return $session->fresh();
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }
}
