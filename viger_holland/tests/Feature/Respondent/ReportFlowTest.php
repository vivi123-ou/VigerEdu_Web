<?php

namespace Tests\Feature\Respondent;

use App\Models\Setting;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Services\AssessmentService;
use App\Modules\Assessment\Support\TestConfig;
use App\Modules\Respondent\Mail\OtpCodeMail;
use App\Modules\Respondent\Models\Consent;
use App\Modules\Respondent\Models\Respondent;
use App\Modules\Respondent\Services\Turnstile;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\FormFieldSeeder;
use Database\Seeders\QuestionBankSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

/**
 * Luồng khách H4: kết quả sơ lược → form thông tin (có SĐT phụ huynh bắt buộc) → mã email → báo cáo đầy đủ chỉ hiện một lần.
 */
class ReportFlowTest extends TestCase
{
    use RefreshDatabase;

    /** Điểm từng câu theo nhóm để ra hồ sơ HS01 của Thầy (khách thấy IS). */
    private const HS01 = [
        'I' => [5, 5, 5, 5, 4, 4], 'S' => [5, 5, 4, 4, 4, 4], 'A' => [5, 4, 4, 4, 4, 4],
        'R' => [4, 4, 3, 3, 3, 3], 'C' => [4, 3, 3, 3, 3, 3], 'E' => [3, 3, 2, 2, 2, 2],
    ];

    private const FORM = [
        'ho_ten' => 'Nguyễn Minh Anh',
        'ngay_sinh' => '2009-03-15',
        'email' => 'MinhAnh@Gmail.com ',
        'sdt_phu_huynh' => '+84 912.345.678',
        'so_dien_thoai' => '0987 654 321',
        'truong' => 'THPT Lê Quý Đôn',
        'tinh' => 'TP. Hồ Chí Minh',
        'doi_tuong' => 'HS',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SettingSeeder::class, CatalogSeeder::class, QuestionBankSeeder::class, TestConfigSeeder::class, FormFieldSeeder::class]);
        Mail::fake();
    }

    public function test_form_needs_a_finished_test(): void
    {
        $this->get(route('report.request'))->assertRedirect(route('home'));

        $session = app(AssessmentService::class)->start(null, null);
        $this->withSession(['holland.test' => $session->uuid])->get(route('report.request'))->assertRedirect(route('test.show'));
    }

    public function test_form_has_the_agreed_fields_with_parent_phone_required_and_no_consent_sentence(): void
    {
        $this->finished();

        $this->get(route('report.request'))->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->assertSee('Cập nhật thông tin để xem báo cáo đầy đủ')
            ->assertSeeInOrder(['Họ tên', 'Ngày sinh của học sinh', 'Email', 'Điện thoại / Zalo của phụ huynh', 'Điện thoại / Zalo của học sinh', 'Trường', 'Tỉnh / thành phố', 'Thuộc đối tượng'])
            ->assertSee('Xem báo cáo')
            ->assertDontSee('đồng ý')
            ->assertDontSee('type="checkbox"', false)
            ->assertDontSee('cf-turnstile', false);
    }

    public function test_required_fields_and_formats_are_checked_in_plain_vietnamese(): void
    {
        $this->finished();

        $this->from(route('report.request'))->post(route('report.submit'), ['ho_ten' => 'A', 'email' => 'sai-email', 'so_dien_thoai' => '12345'])
            ->assertRedirect(route('report.request'))
            ->assertSessionHasErrors([
                'sdt_phu_huynh' => 'Vui lòng nhập điện thoại / Zalo của phụ huynh.',
                'email' => 'Email chưa đúng. Ví dụ: ten@gmail.com.',
                'so_dien_thoai' => 'Điện thoại / Zalo của học sinh chưa đúng. Ví dụ: 0912 345 678.',
                'doi_tuong' => 'Vui lòng chọn thuộc đối tượng.',
                'ngay_sinh' => 'Vui lòng chọn ngày sinh của học sinh.',
            ]);
        Mail::assertNothingSent();

        // SĐT học sinh không bắt buộc.
        $this->post(route('report.submit'), ['so_dien_thoai' => ''] + self::FORM)->assertRedirect(route('report.code'));
    }

    public function test_birth_date_is_picked_on_a_calendar_within_a_sensible_range(): void
    {
        $this->finished();

        $this->get(route('report.request'))->assertSee('type="date"', false)
            ->assertSee('max="'.now()->subYears(5)->toDateString().'"', false);

        foreach (['2030-01-01', '15/03/2009', now()->subYears(80)->toDateString()] as $bad) {
            $this->from(route('report.request'))->post(route('report.submit'), ['ngay_sinh' => $bad] + self::FORM)
                ->assertSessionHasErrors(['ngay_sinh' => 'Ngày sinh của học sinh chưa đúng.']);
        }
        Mail::assertNothingSent();
    }

    public function test_parent_filling_the_form_hides_student_phone_and_renames_email(): void
    {
        $this->finished();

        $this->from(route('report.request'))
            ->post(route('report.submit'), ['doi_tuong' => 'PH', 'so_dien_thoai' => 'không phải số', 'email' => ''] + self::FORM)
            ->assertSessionHasErrors(['email' => 'Vui lòng nhập email của học sinh.'])
            ->assertSessionDoesntHaveErrors('so_dien_thoai');

        $this->get(route('report.request'))->assertSee('Email của học sinh')
            ->assertSee('data-field="so_dien_thoai"', false)->assertSee('hidden', false);

        $this->post(route('report.submit'), ['doi_tuong' => 'PH', 'so_dien_thoai' => 'không phải số'] + self::FORM)->assertRedirect(route('report.code'));
        $session = TestSession::query()->sole();
        $this->assertSame(['ho_ten', 'ngay_sinh', 'email', 'sdt_phu_huynh', 'truong', 'tinh', 'doi_tuong'], $session->fieldValues()->orderBy('id')->pluck('key')->all(),
            'Ô SĐT học sinh bị ẩn thì không lưu');
    }

    public function test_full_flow_sends_a_code_saves_encrypted_data_and_keeps_the_report_only_in_the_same_tab(): void
    {
        $session = $this->finished();

        $this->post(route('report.submit'), self::FORM)->assertRedirect(route('report.code'));
        $code = $this->sentCode('minhanh@gmail.com');

        // Dữ liệu khách điền lưu mã hóa.
        $raw = DB::table('field_values')->pluck('value')->implode(' ');
        $this->assertStringNotContainsString('Nguyễn', $raw);
        $this->assertStringNotContainsString('0912345678', $raw);
        $this->assertSame('0912345678', $session->fieldValues()->where('key', 'sdt_phu_huynh')->value('value'), 'SĐT chuẩn hóa +84 → 0');

        $this->get(route('report.code'))->assertOk()
            ->assertSee('m•••••h@gmail.com')
            ->assertDontSee('minhanh@gmail.com')
            ->assertDontSee('Máy chạy thử')
            ->assertSee('Gửi lại mã')->assertSee('Sửa email');

        $wrong = $code === '000000' ? '111111' : '000000';
        $this->from(route('report.code'))->post(route('report.verify'), ['code' => $wrong])
            ->assertSessionHasErrors(['code' => 'Mã chưa đúng. Bạn còn 4 lần thử.']);
        $this->get(route('report.show'))->assertRedirect(route('report.request'), 'Chưa nhập đúng mã thì chưa có báo cáo');

        $this->post(route('report.verify'), ['code' => $code])->assertRedirect(route('report.show'));

        $all = collect($session->refresh()->result['customer']['majors'])->pluck('name_vi');
        $report = $this->get(route('report.show'))->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->assertSee('Báo cáo sở thích nghề nghiệp')
            ->assertSee('Nghiên cứu – Xã hội')
            ->assertSeeInOrder(['Nhóm sở thích của bạn', 'Mức độ quan tâm theo 6 nhóm', 'Ngành học tại Đức phù hợp với bạn', 'Bước tiếp theo'])
            ->assertSee('Rất quan tâm')
            ->assertSee('Nhà nghiên cứu tâm lý học (Psychologieforscher)')
            ->assertSee('Bạn thích quan sát, đặt câu hỏi')
            ->assertSee('Môi trường hợp thường có cả nghiên cứu lẫn làm việc với người')
            ->assertSee('Làm lại với câu hỏi mới')
            ->assertDontSee('Nguyễn Minh Anh')
            ->assertDontSee('minhanh@gmail.com')
            ->assertDontSeeText('%')
            ->assertDontSee('Cần tra')
            ->assertDontSee('ISA')
            ->assertDontSee('PDF')
            ->assertDontSee('Mã báo cáo', 'Mã báo cáo chỉ dùng nội bộ (Vân chốt 18/11)');
        foreach ($all as $name) {
            $report->assertSee($name);
        }

        $session->refresh();
        $this->assertSame(TestSession::STATUS_COMPLETED, $session->status);
        $this->assertNotNull($session->verified_at);
        $this->assertNotNull($session->report_viewed_at);

        $respondent = $session->respondent;
        $this->assertSame('Nguyễn Minh Anh', $respondent->full_name);
        $this->assertSame('minhanh@gmail.com', $respondent->email);
        $this->assertSame('0912345678', $respondent->parent_phone);
        $this->assertSame('0987654321', $respondent->phone);
        $this->assertSame(Respondent::hashOf('0912345678'), $respondent->parent_phone_hash);
        $this->assertSame(2009, $respondent->birth_year, 'CRM nhận năm sinh tách từ ngày sinh');
        $this->assertSame('2009-03-15', $respondent->birth_date);
        $this->assertStringNotContainsString('2009-03-15', (string) DB::table('respondents')->value('birth_date'), 'Ngày sinh lưu mã hóa');
        $this->assertSame('HS', $respondent->role);
        $this->assertStringNotContainsString('minhanh', (string) DB::table('respondents')->value('email'));
        $this->assertSame(0, Consent::query()->count(), 'Không có câu đồng ý thì không ghi lời đồng ý');

        // Lần đầu: thẻ nhận chìa khóa xem (khach.js cất vào sessionStorage, đóng thẻ là mất).
        $this->assertSame(1, preg_match('/data-key="([^"]+)"/', $report->getContent(), $match));
        $key = $match[1];

        // Tải lại trang trong cùng thẻ: trang không tự in báo cáo, khach.js gửi chìa khóa để lấy lại.
        $this->get(route('report.show'))->assertOk()
            ->assertDontSee('Ngành học tại Đức phù hợp với bạn')->assertDontSee('data-key=', false)
            ->assertSee('data-report-closed', false)->assertSee('Làm bài lại');
        $this->postJson(route('report.view'), ['key' => $key])->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')
            ->assertSee('Ngành học tại Đức phù hợp với bạn')->assertDontSee('Nguyễn Minh Anh');

        // Thẻ đã đóng / thẻ mới (không có chìa) hoặc chìa sai: không lấy được báo cáo, trang hiện "Báo cáo đã đóng".
        $this->postJson(route('report.view'), [])->assertNotFound();
        $this->postJson(route('report.view'), ['key' => 'chia-sai'])->assertNotFound();
        $this->get(route('report.request'))->assertRedirect(route('report.show'));

        // Làm bài lượt mới: chìa cũ hết tác dụng.
        $this->post(route('test.start'));
        $this->postJson(route('report.view'), ['key' => $key])->assertNotFound();
    }

    public function test_whole_journey_over_http_is_not_blocked_by_rate_limits(): void
    {
        $this->post(route('test.start'))->assertRedirect(route('test.show'));
        $session = TestSession::query()->latest('id')->firstOrFail();
        $pages = $session->sessionQuestions()->orderBy('display_order')->get()->chunk(6)->values();
        $next = array_map(fn () => 0, self::HS01);
        foreach ($pages as $index => $items) {
            $answers = [];
            foreach ($items as $item) {
                $answers[$item->question_id] = self::HS01[$item->group][$next[$item->group]++];
            }
            $this->post(route('test.submit'), ['page' => $index + 1, 'direction' => 'next', 'answers' => $answers])->assertRedirect();
        }
        $this->get(route('test.result'))->assertOk();

        // Gõ sai vài lần vẫn không bị chặn ở bước sau (mỗi bước một bộ đếm riêng).
        $this->post(route('report.submit'), self::FORM)->assertRedirect(route('report.code'));
        $code = $this->sentCode('minhanh@gmail.com');
        $wrong = $code === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('report.verify'), ['code' => $wrong])->assertSessionHasErrors('code');
        }
        $this->post(route('report.verify'), ['code' => $code])->assertRedirect(route('report.show'));
        $this->get(route('report.show'))->assertOk()->assertSee('Ngành học tại Đức phù hợp với bạn');
    }

    public function test_customer_sees_two_letters_unless_admin_turns_on_the_third_letter(): void
    {
        $profile = [
            'I' => [5, 5, 5, 5, 5, 5], 'S' => [5, 5, 5, 5, 5, 4], 'A' => [5, 5, 5, 4, 4, 4],
            'R' => [3, 3, 3, 3, 3, 3], 'C' => [3, 3, 3, 2, 2, 2], 'E' => [2, 2, 2, 2, 2, 2],
        ];

        // Mặc định (Vân chốt 18/11): Sáng tạo 4,5 vượt 4,2 nhưng khách vẫn chỉ thấy IS; nội bộ vẫn có đủ ISA.
        $session = $this->finished($profile);
        $this->assertSame('ISA', $session->code3);
        $this->assertTrue($session->result['third_detail']);
        $this->assertSame('IS', $session->result['customer']['code']);
        $this->post(route('report.submit'), self::FORM);
        $this->post(route('report.verify'), ['code' => $this->sentCode('minhanh@gmail.com')]);
        $this->get(route('report.show'))->assertSee('aria-label="Mã Holland IS"', false)->assertDontSee('ISA')->assertDontSee('Chữ thứ ba');

        // Admin bật "Cho khách thấy chữ thứ 3": khách thấy ISA và đọc đoạn "Chữ thứ ba" đúng nguyên văn đã duyệt.
        TestConfig::save(['show_third_letter' => true] + TestConfig::current(), null, 'Cho khách thấy chữ thứ 3');
        $this->finished($profile);
        $this->post(route('report.submit'), self::FORM);
        $this->post(route('report.verify'), ['code' => $this->sentCode('minhanh@gmail.com', 1)]);
        $this->get(route('report.show'))
            ->assertSee('aria-label="Mã Holland ISA"', false)
            ->assertSeeInOrder(['Nhóm nổi bật nhất – Nghiên cứu (I)', 'Nhóm thứ hai – Xã hội (S)', 'Chữ thứ ba – Sáng tạo (A)', 'Khi hai nhóm kết hợp'])
            ->assertSee('Nhóm Sáng tạo của bạn cũng ở mức cao, nên bạn thích thêm việc thử ý tưởng mới và làm ra sản phẩm có dấu ấn riêng.')
            ->assertDontSee('Design und Innovation');
    }

    public function test_code_locks_after_too_many_wrong_tries_even_with_a_new_attempt(): void
    {
        $this->finished();
        $this->post(route('report.submit'), self::FORM);
        $code = $this->sentCode('minhanh@gmail.com');
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 1; $i <= 4; $i++) {
            $this->post(route('report.verify'), ['code' => $wrong])->assertSessionHasErrors(['code' => 'Mã chưa đúng. Bạn còn '.(5 - $i).' lần thử.']);
        }
        $this->post(route('report.verify'), ['code' => $wrong])
            ->assertSessionHasErrors(['code' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau 30 phút.']);
        $this->post(route('report.verify'), ['code' => $code])
            ->assertSessionHasErrors(['code' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau 30 phút.']);

        // Làm lượt mới với cùng email vẫn bị khóa, không nhận mã mới.
        $this->finished();
        $this->from(route('report.request'))->post(route('report.submit'), self::FORM)
            ->assertSessionHasErrors(['email' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau 30 phút.']);
        Mail::assertSentCount(1);

        $this->travel(31)->minutes();
        $this->post(route('report.submit'), self::FORM)->assertRedirect(route('report.code'));
        Mail::assertSentCount(2);
    }

    public function test_code_expires_and_resend_waits_then_replaces_the_old_code(): void
    {
        $this->finished();
        $this->post(route('report.submit'), self::FORM);
        $first = $this->sentCode('minhanh@gmail.com');

        $this->from(route('report.code'))->post(route('report.resend'))
            ->assertSessionHasErrors('code');
        $this->assertStringContainsString('giây rồi gửi lại mã', session('errors')->first('code'));
        Mail::assertSentCount(1);

        $this->travel(6)->minutes();
        $this->post(route('report.verify'), ['code' => $first])
            ->assertSessionHasErrors(['code' => 'Mã đã hết hạn. Bạn bấm "Gửi lại mã" để nhận mã mới.']);

        $this->post(route('report.resend'))->assertRedirect(route('report.code'))->assertSessionHas('status');
        $second = $this->sentCode('minhanh@gmail.com', 1);
        if ($second !== $first) {
            $this->post(route('report.verify'), ['code' => $first])->assertSessionHasErrors('code');
        }
        $this->post(route('report.verify'), ['code' => $second])->assertRedirect(route('report.show'));
    }

    public function test_fixing_a_mistyped_email_sends_to_the_new_address_without_waiting(): void
    {
        $this->finished();
        $this->post(route('report.submit'), ['email' => 'sai@gmial.com'] + self::FORM);
        $this->get(route('report.request'))->assertSee('value="sai@gmial.com"', false)->assertSee('value="Nguyễn Minh Anh"', false);

        $this->post(route('report.submit'), self::FORM)->assertRedirect(route('report.code'));
        $code = $this->sentCode('minhanh@gmail.com');
        $this->post(route('report.verify'), ['code' => $code])->assertRedirect(route('report.show'));
        $this->assertSame('minhanh@gmail.com', Respondent::query()->sole()->email);
    }

    public function test_too_many_codes_for_one_email_within_an_hour_are_refused(): void
    {
        Setting::put('otp_max_per_hour', '2', 'report');
        Setting::flushCache();

        for ($i = 1; $i <= 2; $i++) {
            $this->finished();
            $this->post(route('report.submit'), self::FORM)->assertRedirect(route('report.code'));
        }
        $this->finished();
        $this->from(route('report.request'))->post(route('report.submit'), self::FORM)
            ->assertSessionHasErrors(['email' => 'Bạn đã yêu cầu nhiều mã. Vui lòng thử lại sau ít phút.']);
        Mail::assertSentCount(2);
    }

    public function test_same_email_on_a_new_attempt_reuses_the_customer_record(): void
    {
        foreach (['Nguyễn Minh Anh', 'Nguyễn M. Anh'] as $index => $name) {
            $this->finished();
            $this->post(route('report.submit'), ['ho_ten' => $name] + self::FORM);
            $this->post(route('report.verify'), ['code' => $this->sentCode('minhanh@gmail.com', $index)])->assertRedirect(route('report.show'));
        }

        $respondent = Respondent::query()->sole();
        $this->assertSame('Nguyễn M. Anh', $respondent->full_name);
        $this->assertSame(2, $respondent->testSessions()->count());
    }

    public function test_consent_sentence_shows_and_is_recorded_only_when_admin_writes_one(): void
    {
        Setting::put('report_consent_text', 'Bấm "Xem báo cáo" là bạn đồng ý cho VIGER EDU liên hệ tư vấn.', 'report');
        Setting::put('report_consent_version', 'dieu-khoan-2026-11', 'report');
        Setting::flushCache();
        $this->finished();

        $this->get(route('report.request'))->assertSee('Bấm &quot;Xem báo cáo&quot; là bạn đồng ý', false);
        $this->post(route('report.submit'), self::FORM);
        $this->post(route('report.verify'), ['code' => $this->sentCode('minhanh@gmail.com')]);

        $consent = Consent::query()->sole();
        $this->assertSame('Bấm "Xem báo cáo" là bạn đồng ý cho VIGER EDU liên hệ tư vấn.', $consent->text_snapshot);
        $this->assertSame('dieu-khoan-2026-11', $consent->version);
    }

    public function test_turnstile_runs_only_when_keys_are_set_and_blocks_failed_checks(): void
    {
        config(['services.turnstile.site_key' => 'site', 'services.turnstile.secret_key' => 'secret']);
        Http::fake([Turnstile::VERIFY_URL => Http::sequence()->push(['success' => false])->push(['success' => true])]);
        $this->finished();

        $this->get(route('report.request'))->assertSee('cf-turnstile', false)->assertSee('data-sitekey="site"', false);
        $this->from(route('report.request'))->post(route('report.submit'), self::FORM + ['cf-turnstile-response' => 'token'])
            ->assertSessionHasErrors(['form' => 'Chưa kiểm tra được trình duyệt. Bạn tải lại trang rồi thử lại.']);
        Mail::assertNothingSent();

        $this->post(route('report.submit'), self::FORM + ['cf-turnstile-response' => 'token'])->assertRedirect(route('report.code'));
    }

    public function test_mail_failure_is_reported_without_the_email_address_and_does_not_block_retry(): void
    {
        Exceptions::fake();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Expected response code 250 but got 550 for minhanh@gmail.com'));
        $this->finished();

        $this->from(route('report.request'))->post(route('report.submit'), self::FORM)
            ->assertSessionHasErrors(['form' => 'Chưa gửi được mã. Bạn kiểm tra lại email hoặc thử lại sau ít phút.']);

        Exceptions::assertReported(fn (RuntimeException $e) => ! str_contains($e->getMessage(), 'minhanh') && str_contains($e->getMessage(), 'Không gửi được email'));
        $this->assertDatabaseCount('otp_codes', 0);
    }

    /**
     * Một lượt đã làm xong bài của trình duyệt đang thử (mặc định hồ sơ HS01, khách thấy IS).
     *
     * @param  array<string, list<int>>  $profile  điểm từng câu theo nhóm
     */
    private function finished(array $profile = self::HS01): TestSession
    {
        $service = app(AssessmentService::class);
        $session = $service->start(null, null);
        $answers = [];
        $next = array_map(fn () => 0, $profile);
        foreach ($session->sessionQuestions()->get() as $item) {
            $answers[$item->question_id] = ['value' => $profile[$item->group][$next[$item->group]++], 'ms' => 4000];
        }
        $session = $service->finish($session, $answers);
        $this->withSession(['holland.test' => $session->uuid]);

        return $session;
    }

    /**
     * Mã trong email thứ $index gửi tới địa chỉ $email.
     */
    private function sentCode(string $email, int $index = 0): string
    {
        $mails = Mail::sent(OtpCodeMail::class, fn (OtpCodeMail $mail) => $mail->hasTo($email))->values();
        $this->assertArrayHasKey($index, $mails->all(), 'Chưa gửi mã tới '.$email);

        return $mails[$index]->code;
    }
}
