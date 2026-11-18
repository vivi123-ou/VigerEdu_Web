<?php

namespace Tests\Feature\CrmSync;

use App\Models\Setting;
use App\Models\User;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Services\AssessmentService;
use App\Modules\CrmSync\Filament\Pages\CrmConnection;
use App\Modules\CrmSync\Models\CrmOutbox;
use App\Modules\CrmSync\Services\CrmConfig;
use App\Modules\CrmSync\Services\CrmSync;
use App\Modules\Respondent\Mail\OtpCodeMail;
use App\Modules\Respondent\Models\FormField;
use App\Modules\Respondent\Models\Respondent;
use App\Modules\Respondent\Support\InternalReport;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * H5: gửi khách sang CRM (lead/2, events/1) qua hàng đợi crm_outbox, tự gửi lại khi lỗi, ghi hành vi,
 * trang "Gửi khách sang CRM". CRM chưa có nên giả lập bằng Http::fake().
 */
class CrmSyncTest extends TestCase
{
    use RefreshDatabase;

    private const HS01 = [
        'I' => [5, 5, 5, 5, 4, 4], 'S' => [5, 5, 4, 4, 4, 4], 'A' => [5, 4, 4, 4, 4, 4],
        'R' => [4, 4, 3, 3, 3, 3], 'C' => [4, 3, 3, 3, 3, 3], 'E' => [3, 3, 2, 2, 2, 2],
    ];

    private const FORM = [
        'ho_ten' => 'Nguyễn Minh Anh', 'ngay_sinh' => '2009-03-15', 'email' => 'minhanh@gmail.com',
        'sdt_phu_huynh' => '0912345678', 'so_dien_thoai' => '0987654321', 'truong' => 'THPT Lê Quý Đôn',
        'tinh' => 'TP. Hồ Chí Minh', 'doi_tuong' => 'HS',
    ];

    private const CRM = 'https://crm.test';

    /** @var list<string> */
    private array $logs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Mail::fake();
        Event::listen(MessageLogged::class, fn (MessageLogged $e) => $this->logs[] = $e->message.' '.json_encode($e->context, JSON_UNESCAPED_UNICODE));
    }

    protected function tearDown(): void
    {
        foreach (['Nguyễn Minh Anh', 'minhanh@gmail.com', '0912345678', '912345678', '0987654321'] as $private) {
            $this->assertStringNotContainsString($private, implode("\n", $this->logs), 'Log không được có dữ liệu cá nhân');
        }

        parent::tearDown();
    }

    public function test_connection_off_keeps_leads_waiting_without_any_request_and_customer_still_sees_report(): void
    {
        Http::fake();

        $this->customerUnlocksReport();

        $outbox = CrmOutbox::query()->sole();
        $this->assertSame(CrmOutbox::DISABLED, $outbox->status);
        $this->assertSame('lead/2', $outbox->schema);
        $this->assertStringNotContainsString('Nguyễn', (string) DB::table('crm_outbox')->value('payload'), 'Gói lưu mã hóa');
        Http::assertNothingSent();

        // Bật kết nối: khách đang chờ được gửi.
        $this->connect();
        Http::fake([self::CRM.'/*' => Http::response(['customer_code' => 'KH0001'])]);
        $this->assertSame(1, app(CrmSync::class)->resumeDisabled());
        $this->assertSame(CrmOutbox::SENT, $outbox->fresh()->status);
    }

    public function test_lead_follows_the_contract_and_only_sends_filled_fields(): void
    {
        $this->connect();
        Http::fake([self::CRM.'/*' => Http::response(['customer_code' => 'KH0001', 'is_new' => true])]);

        $this->customerUnlocksReport();
        $session = TestSession::query()->latest('id')->firstOrFail();

        Http::assertSent(function (Request $request) use ($session) {
            $p = $request->data();
            $this->assertSame(self::CRM.'/api/v1/leads', $request->url());
            $this->assertSame('tok-holland', $request->header('X-Api-Token')[0]);
            $this->assertSame('lead/2', $p['schema_version']);
            $this->assertSame('holland', $p['channel']);
            $this->assertSame(InternalReport::code($session), $p['external_ref']);
            $this->assertSame(['agreed' => false], $p['consent'], 'Không có câu đồng ý thì không được gửi agreed = true');
            $this->assertSame('Nguyễn Minh Anh', $p['contact']['full_name']);
            $this->assertSame('+84987654321', $p['contact']['phone']);
            $this->assertSame('+84912345678', $p['guardian']['phone']);
            $this->assertSame(2009, $p['contact']['birth_year']);
            $this->assertSame('HS', $p['contact']['role']);
            $this->assertSame('TP. Hồ Chí Minh', $p['contact']['province']);
            $this->assertFalse($p['contact']['phone_verified']);
            $this->assertTrue($p['contact']['email_verified']);
            $this->assertSame('THPT Lê Quý Đôn', $p['profile']['current_school']);
            $this->assertSame('holland-result/3', $p['holland_result']['schema_version']);
            $this->assertSame('ISA', $p['holland_result']['code_full']);
            $this->assertSame('IS', $p['holland_result']['code_public']);
            $this->assertCount(36, $p['quiz']['answers']);
            $this->assertStringContainsString('/admin/bao-cao-noi-bo?ma='.$session->uuid, $p['quiz']['view_url']);
            $this->assertSame(['form_submitted', 'report_unlocked'], array_column($p['events'], 'type'));
            $this->assertArrayNotHasKey('__tracking_ids', $p);
            $this->assertArrayNotHasKey('extra_fields', $p, 'Không có ô mở rộng nào được điền');
            $this->assertStringNotContainsString('2009-03-15', json_encode($p), 'Chỉ gửi năm sinh');
            $this->assertStringNotContainsString('null', json_encode($p), 'Không gửi giá trị rỗng');

            return true;
        });

        $outbox = CrmOutbox::query()->sole();
        $this->assertSame(CrmOutbox::SENT, $outbox->status);
        $this->assertSame('KH0001', Respondent::query()->sole()->crm_customer_code);
    }

    public function test_admin_added_fields_go_to_extra_fields_and_crm_keys_move_values(): void
    {
        $this->connect();
        Http::fake([self::CRM.'/*' => Http::response(['customer_code' => 'KH0001'])]);
        FormField::query()->create(['key' => 'truong_chuyen', 'label' => 'Học trường chuyên?', 'type' => 'select', 'to_crm' => true, 'is_visible' => true,
            'options' => [['value' => 'co', 'label' => 'Có'], ['value' => 'khong', 'label' => 'Không']], 'sort_order' => 20]);
        FormField::query()->create(['key' => 'ghi_chu_noi_bo', 'label' => 'Ghi chú', 'type' => 'text', 'to_crm' => false, 'is_visible' => true, 'sort_order' => 21]);
        FormField::query()->where('key', 'truong')->update(['crm_key' => 'current_level']);

        $this->customerUnlocksReport(['truong_chuyen' => 'co', 'ghi_chu_noi_bo' => 'không gửi']);

        Http::assertSent(function (Request $request) {
            $p = $request->data();
            $this->assertSame(['truong_chuyen' => 'co'], $p['extra_fields']);
            $this->assertSame('THPT Lê Quý Đôn', $p['profile']['current_level'], 'Đổi crm_key trên admin thì gói đổi theo');
            $this->assertStringNotContainsString('không gửi', json_encode($p, JSON_UNESCAPED_UNICODE));

            return true;
        });
    }

    public function test_crm_errors_are_retried_with_the_same_event_id_and_never_block_the_customer(): void
    {
        $this->connect();
        Http::fake([self::CRM.'/*' => Http::sequence()->push(['message' => 'lỗi'], 500)->push(['customer_code' => 'KH0002'], 201)]);

        $this->customerUnlocksReport()->assertRedirect(route('report.show'));
        $this->get(route('report.show'))->assertOk()->assertSee('Ngành học tại Đức phù hợp với bạn');

        $outbox = CrmOutbox::query()->sole();
        $this->assertSame(CrmOutbox::PENDING, $outbox->status);
        $this->assertSame(1, $outbox->tries);
        $this->assertSame(500, $outbox->last_http_status);
        $this->assertTrue($outbox->next_try_at->isFuture());

        $this->artisan('crm:retry')->assertSuccessful();
        $this->assertSame(CrmOutbox::PENDING, $outbox->fresh()->status, 'Chưa tới giờ gửi lại');

        $this->travel(2)->minutes();
        $this->artisan('crm:retry')->assertSuccessful();
        $this->assertSame(CrmOutbox::SENT, $outbox->fresh()->status);

        $ids = collect(Http::recorded())->map(fn ($pair) => $pair[0]->data()['event_id'] ?? null)->filter()->unique();
        $this->assertCount(1, $ids, 'Gửi lại vẫn giữ nguyên event_id');
    }

    public function test_rejected_data_fails_without_personal_data_in_the_error(): void
    {
        $this->connect();
        Http::fake([self::CRM.'/*' => Http::response(['errors' => ['contact.phone' => ['0987654321 sai'], 'consent.agreed' => ['bắt buộc']]], 422)]);

        $this->customerUnlocksReport();

        $outbox = CrmOutbox::query()->sole();
        $this->assertSame(CrmOutbox::FAILED, $outbox->status);
        $this->assertStringContainsString('contact.phone, consent.agreed', (string) $outbox->last_error);
        $this->assertStringNotContainsString('0987654321', (string) $outbox->last_error);
    }

    public function test_retries_stop_after_the_schedule_and_admin_can_send_again(): void
    {
        $this->connect();
        Setting::put('crm_retry_minutes', '1,2', 'crm');
        Setting::flushCache();
        Http::fake([self::CRM.'/*' => Http::sequence()->push([], 503)->push([], 503)->push([], 503)->push(['customer_code' => 'KH0003'])]);

        $this->customerUnlocksReport();
        foreach ([2, 3] as $minutes) {
            $this->travel($minutes)->minutes();
            $this->artisan('crm:retry');
        }
        $outbox = CrmOutbox::query()->sole();
        $this->assertSame(CrmOutbox::FAILED, $outbox->status);
        $this->assertSame(3, $outbox->tries);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        Livewire::test(CrmConnection::class)->callTableAction('retry', $outbox);
        $this->assertSame(CrmOutbox::SENT, $outbox->fresh()->status);
    }

    public function test_later_behaviour_goes_as_events_with_the_customer_code(): void
    {
        $this->connect();
        Http::fake([self::CRM.'/*' => Http::response(['customer_code' => 'KH0009'])]);

        $this->customerUnlocksReport();
        $report = $this->get(route('report.show'));
        preg_match('/data-key="([^"]+)"/', $report->getContent(), $match);
        $this->postJson(route('report.view'), ['key' => $match[1]])->assertOk();
        $this->post(route('tracking.event'), ['type' => 'cta_zalo_clicked'])->assertNoContent();
        $this->post(route('tracking.event'), ['type' => 'xoa_du_lieu'])->assertNoContent();

        $events = collect(Http::recorded())->map(fn ($pair) => $pair[0])->filter(fn (Request $r) => str_ends_with($r->url(), '/api/v1/events'));
        $this->assertNotEmpty($events);
        $types = $events->flatMap(fn (Request $r) => array_column($r->data()['events'], 'type'))->all();
        $this->assertSame(['report_viewed', 'report_viewed', 'cta_zalo_clicked'], $types);
        $events->each(fn (Request $r) => $this->assertSame('KH0009', $r->data()['customer_code']));
    }

    public function test_returning_customer_lead_carries_the_crm_customer_code(): void
    {
        $this->connect();
        Http::fake([self::CRM.'/*' => Http::response(['customer_code' => 'KH0042'])]);

        $this->customerUnlocksReport();
        $this->customerUnlocksReport(['ho_ten' => 'Nguyễn M. Anh'], 1);

        $leads = collect(Http::recorded())->map(fn ($pair) => $pair[0])->filter(fn (Request $r) => str_ends_with($r->url(), '/api/v1/leads'))->values();
        $this->assertCount(2, $leads);
        $this->assertArrayNotHasKey('customer_code', $leads[0]->data());
        $this->assertSame('KH0042', $leads[1]->data()['customer_code']);
    }

    public function test_consent_is_sent_as_agreed_only_when_the_customer_saw_the_sentence(): void
    {
        $this->connect();
        Setting::put('report_consent_text', 'Bấm "Xem báo cáo" là bạn đồng ý cho VIGER EDU liên hệ tư vấn.', 'report');
        Setting::put('report_consent_version', 'dieu-khoan-2026-11', 'report');
        Setting::flushCache();
        Http::fake([self::CRM.'/*' => Http::response(['customer_code' => 'KH0001'])]);

        $this->customerUnlocksReport();

        Http::assertSent(function (Request $request) {
            $consent = $request->data()['consent'];
            $this->assertTrue($consent['agreed']);
            $this->assertSame('dieu-khoan-2026-11', $consent['version']);
            $this->assertArrayHasKey('agreed_at', $consent);

            return true;
        });
    }

    public function test_admin_page_saves_token_encrypted_checks_connection_and_masks_phones(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->customerUnlocksReport();
        $this->actingAs($admin);

        Livewire::test(CrmConnection::class)
            ->fillForm(['crm_enabled' => true, 'crm_url' => self::CRM, 'crm_token' => 'tok-bi-mat-1234', 'crm_retry_minutes' => '1,5'])
            ->call('save');
        $this->assertStringNotContainsString('tok-bi-mat', (string) DB::table('settings')->where('key', 'crm_token')->value('value'), 'Token lưu mã hóa');
        $this->assertSame('tok-bi-mat-1234', app(CrmConfig::class)->token());

        $this->get(CrmConnection::getUrl())->assertOk()
            ->assertSee('…1234')->assertDontSee('tok-bi-mat')
            ->assertSee('09xx xxx 678')->assertDontSee('0912345678');

        Http::fake([self::CRM.'/api/v1/ping' => Http::sequence()->push(['ok' => true, 'channel' => 'holland'])->push([], 401)]);
        Livewire::test(CrmConnection::class)->call('ping')->assertNotified('Kết nối tốt');
        Livewire::test(CrmConnection::class)->call('ping')->assertNotified('Sai token');

        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->get(CrmConnection::getUrl())->assertNotFound();
    }

    public function test_payload_command_prints_a_masked_sample_for_the_crm_team(): void
    {
        $this->customerUnlocksReport();
        $session = TestSession::query()->latest('id')->firstOrFail();

        $this->assertSame(0, Artisan::call('crm:payload', ['ma' => InternalReport::code($session)]));
        $output = Artisan::output();
        $this->assertStringContainsString('"schema_version": "lead/2"', $output);
        $this->assertStringContainsString('09xx xxx 678', $output);
        $this->assertStringNotContainsString('0912345678', $output);
        $this->assertStringNotContainsString('minhanh@gmail.com', $output);
        $this->assertStringNotContainsString('Nguyễn Minh Anh', $output);
        $this->assertSame(1, Artisan::call('crm:payload', ['ma' => 'ZZZZZZZZ']));
    }

    private function connect(): void
    {
        Setting::put('crm_enabled', '1', 'crm');
        Setting::put('crm_url', self::CRM, 'crm');
        app(CrmConfig::class)->saveToken('tok-holland');
        Setting::flushCache();
    }

    /**
     * Khách làm xong bài (HS01), điền form, nhập đúng mã email.
     *
     * @param  array<string, string>  $form
     */
    private function customerUnlocksReport(array $form = [], int $mailIndex = 0): TestResponse
    {
        $service = app(AssessmentService::class);
        $session = $service->start(null, null);
        $next = array_map(fn () => 0, self::HS01);
        $answers = [];
        foreach ($session->sessionQuestions()->get() as $item) {
            $answers[$item->question_id] = ['value' => self::HS01[$item->group][$next[$item->group]++], 'ms' => 4000];
        }
        $service->finish($session, $answers);
        $this->withSession(['holland.test' => $session->uuid]);

        $this->post(route('report.submit'), $form + self::FORM)->assertRedirect(route('report.code'));
        $code = Mail::sent(OtpCodeMail::class)->values()[$mailIndex]->code;

        return $this->post(route('report.verify'), ['code' => $code]);
    }
}
