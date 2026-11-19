<?php

namespace App\Modules\Respondent\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Support\CurrentTest;
use App\Modules\Assessment\Support\CustomerReport;
use App\Modules\CrmSync\Services\CrmSync;
use App\Modules\Respondent\Models\OtpCode;
use App\Modules\Respondent\Models\Respondent;
use App\Modules\Respondent\Services\OtpService;
use App\Modules\Respondent\Services\ReportAccessService;
use App\Modules\Respondent\Services\ReportForm;
use App\Modules\Respondent\Services\Turnstile;
use App\Modules\Tracking\Services\Tracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Làm gì:   Luồng khách H4 sau kết quả sơ lược: form thông tin → mã xác nhận qua email → báo cáo đầy đủ.
 *           Báo cáo xem được trong thẻ (tab) đã nhập đúng mã, tải lại vẫn còn; đóng thẻ là mất (Vân chốt 18/11),
 *           muốn xem lại thì làm bài lại. Không email link, không PDF, không chia sẻ, không hiện họ tên.
 * Ai gọi:   routes/web.php (report.request, report.submit, report.code, report.verify, report.resend, report.show, report.view).
 * Dữ liệu:  test_sessions (lượt của trình duyệt), field_values, otp_codes, respondents, consents. Không ghi họ tên,
 *           email, SĐT, mã vào log.
 */
class ReportController extends Controller
{
    /** Băm của chìa khóa xem báo cáo và lượt làm bài nó mở (lưu phía máy chủ). */
    private const REPORT_KEY = 'holland.report';

    /** Chìa khóa gốc, chỉ trao cho trang /bao-cao ngay sau khi nhập đúng mã (một lần), rồi nằm trong sessionStorage của thẻ. */
    private const FRESH_KEY = 'holland.report_key';

    private const CONSENT_KEY = 'holland.consent';

    private const DEV_CODE_KEY = 'holland.dev_code';

    public function __construct(
        private readonly ReportForm $form,
        private readonly OtpService $otp,
        private readonly Turnstile $turnstile,
        private readonly Tracker $tracker,
        private readonly CrmSync $crm,
    ) {}

    public function form(Request $request): View|RedirectResponse
    {
        $session = CurrentTest::get($request);
        if ($redirect = $this->guard($session)) {
            return $redirect;
        }

        $values = array_merge($this->form->values($session), array_filter((array) $request->old(), 'is_string'));

        return view('public.report-form', [
            'fields' => $this->form->fields(),
            'form' => $this->form,
            'values' => $values,
            'consent' => self::consentHtml((string) setting('report_consent_text'), (string) setting('report_privacy_url')),
            'turnstileKey' => $this->turnstile->enabled() ? config('services.turnstile.site_key') : null,
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $session = CurrentTest::get($request);
        if ($redirect = $this->guard($session)) {
            return $redirect;
        }

        $input = $this->form->normalize($request->except(['_token', 'cf-turnstile-response']));
        ['rules' => $rules, 'attributes' => $attributes, 'messages' => $messages] = $this->form->rules($input);
        $data = Validator::make($input, $rules, $messages, $attributes)->validate();

        if (! $this->turnstile->passes($request->input('cf-turnstile-response'), $request->ip())) {
            throw ValidationException::withMessages(['form' => setting('report_bot_error', 'Chưa kiểm tra được trình duyệt. Bạn tải lại trang rồi thử lại.')]);
        }

        $email = (string) $data['email'];
        $this->ensureCanSend($session, $email, 'email');

        $this->form->save($session, $data);
        $request->session()->put(self::CONSENT_KEY, filled(setting('report_consent_text')) ? (string) setting('report_consent_text') : null);

        $response = $this->sendCode($request, $session, $email, 'form');
        $this->tracker->record($session, 'form_submitted');

        return $response;
    }

    public function code(Request $request): View|RedirectResponse
    {
        $session = CurrentTest::get($request);
        if ($redirect = $this->guard($session)) {
            return $redirect;
        }
        $email = $this->form->values($session)['email'] ?? null;
        if ($email === null) {
            return redirect()->route('report.request');
        }

        return view('public.report-code', [
            'maskedEmail' => self::maskEmail($email),
            'wait' => $this->otp->secondsUntilResend($session),
            'length' => OtpService::LENGTH,
            'devCode' => $request->session()->get(self::DEV_CODE_KEY),
        ]);
    }

    public function verify(Request $request, ReportAccessService $access): RedirectResponse
    {
        $session = CurrentTest::get($request);
        if ($redirect = $this->guard($session)) {
            return $redirect;
        }
        $email = $this->form->values($session)['email'] ?? null;
        if ($email === null) {
            return redirect()->route('report.request');
        }

        $code = preg_replace('/\D/', '', (string) $request->input('code')) ?? '';
        if (strlen($code) !== OtpService::LENGTH) {
            throw ValidationException::withMessages(['code' => setting('otp_format_error', 'Vui lòng nhập đủ 6 số.')]);
        }

        $check = $this->otp->verify($session, $email, $code);
        if (! $check['ok']) {
            $message = match ($check['reason']) {
                'wrong' => str_replace('{so_lan}', (string) $check['remaining'], (string) setting('otp_wrong', 'Mã chưa đúng. Bạn còn {so_lan} lần thử.')),
                'locked' => $this->lockedMessage(),
                default => setting('otp_expired', 'Mã đã hết hạn. Bạn bấm "Gửi lại mã" để nhận mã mới.'),
            };
            throw ValidationException::withMessages(['code' => $message]);
        }

        $access->complete($session, $request->session()->pull(self::CONSENT_KEY));
        $session = $session->fresh('respondent');
        $this->tracker->record($session, 'report_unlocked');
        // Gửi khách sang CRM chạy nền: CRM lỗi hay chưa bật thì khách vẫn xem báo cáo bình thường.
        $this->crm->queueLead($session);
        $request->session()->forget(self::DEV_CODE_KEY);
        $key = Str::random(48);
        $request->session()->put(self::REPORT_KEY, ['uuid' => $session->uuid, 'hash' => hash('sha256', $key)]);
        $request->session()->flash(self::FRESH_KEY, $key);

        return redirect()->route('report.show');
    }

    public function resend(Request $request): RedirectResponse
    {
        $session = CurrentTest::get($request);
        if ($redirect = $this->guard($session)) {
            return $redirect;
        }
        $email = $this->form->values($session)['email'] ?? null;
        if ($email === null) {
            return redirect()->route('report.request');
        }

        $this->ensureCanSend($session, $email, 'code', sameEmailOnly: false);

        return $this->sendCode($request, $session, $email, 'code')
            ->with('status', setting('otp_resent', 'Đã gửi mã mới. Bạn kiểm tra hộp thư (cả mục Thư rác / Quảng cáo).'));
    }

    /**
     * Trang báo cáo. Ngay sau khi nhập đúng mã: hiện báo cáo và trao chìa khóa cho thẻ (khach.js cất vào sessionStorage).
     * Tải lại trang: trang trống, khach.js gửi chìa khóa của thẻ lên view() để lấy lại báo cáo; thẻ không có chìa → "Báo cáo đã đóng".
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $session = CurrentTest::get($request);
        if ($session === null) {
            return redirect()->route('home');
        }
        if ($session->verified_at === null) {
            return redirect()->route($session->status === TestSession::STATUS_IN_PROGRESS ? 'test.show' : 'report.request');
        }

        $key = $request->session()->get(self::FRESH_KEY);
        $report = is_string($key) && $this->keyOpens($request, $session, $key) ? CustomerReport::make($session) : null;
        if ($report !== null) {
            if ($session->report_viewed_at === null) {
                $session->update(['report_viewed_at' => now()]);
            }
            $this->tracker->record($session, 'report_viewed');
        }

        return $this->private(response()->view('public.report', ['report' => $report, 'key' => $report ? $key : null]));
    }

    /**
     * Lấy lại thân báo cáo khi tải lại trang, bằng chìa khóa trong sessionStorage của thẻ. Sai chìa → 404 (trang hiện "Báo cáo đã đóng").
     */
    public function view(Request $request): Response
    {
        $session = CurrentTest::get($request);
        $key = $request->input('key');

        abort_unless($session !== null && $session->verified_at !== null && is_string($key) && $this->keyOpens($request, $session, $key), 404);
        $this->tracker->record($session, 'report_viewed', ['reload' => true]);

        return $this->private(response()->view('public.partials.report-body', ['report' => CustomerReport::make($session)]));
    }

    /**
     * Câu đồng ý hiện dưới nút: chữ đã thoát HTML; có link chính sách thì cụm "chính sách bảo mật" thành link.
     */
    public static function consentHtml(string $text, string $privacyUrl): ?HtmlString
    {
        if (trim($text) === '') {
            return null;
        }
        $html = e($text);
        if (preg_match('#^https?://#', $privacyUrl)) {
            $html = str_replace(e('chính sách bảo mật'), '<a href="'.e($privacyUrl).'" target="_blank" rel="noopener">'.e('chính sách bảo mật').'</a>', $html);
        }

        return new HtmlString($html);
    }

    /**
     * Che email: m•••••h@gmail.com.
     */
    public static function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $first = mb_substr($local, 0, 1);
        $last = mb_strlen($local) > 2 ? mb_substr($local, -1) : '';

        return $first.str_repeat('•', max(3, mb_strlen($local) - 2)).$last.'@'.$domain;
    }

    /**
     * Chưa làm bài → trang đầu; đang làm → trang làm bài; đã xác nhận (báo cáo đã hiện) → trang "Báo cáo đã đóng".
     */
    private function guard(?TestSession $session): ?RedirectResponse
    {
        if ($session === null) {
            return redirect()->route('home');
        }
        if ($session->status === TestSession::STATUS_IN_PROGRESS) {
            return redirect()->route('test.show');
        }
        if ($session->verified_at !== null) {
            return redirect()->route('report.show');
        }

        return null;
    }

    /**
     * Chìa khóa có mở được báo cáo của lượt làm bài đang gắn với trình duyệt không (làm bài lượt mới thì chìa cũ hết tác dụng).
     */
    private function keyOpens(Request $request, TestSession $session, string $key): bool
    {
        $stored = $request->session()->get(self::REPORT_KEY);

        return is_array($stored) && ($stored['uuid'] ?? null) === $session->uuid
            && hash_equals((string) ($stored['hash'] ?? ''), hash('sha256', $key));
    }

    /**
     * Báo cáo có dữ liệu riêng của khách: không cho trình duyệt / máy chủ trung gian lưu lại.
     */
    private function private(Response $response): Response
    {
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')->header('Pragma', 'no-cache');
    }

    /**
     * Chặn gửi mã khi email đang khóa, quá số mã trong giờ, hoặc chưa hết thời gian chờ (đổi sang email khác thì không phải chờ).
     */
    private function ensureCanSend(TestSession $session, string $email, string $errorKey, bool $sameEmailOnly = true): void
    {
        if ($this->otp->lockedUntil($email) !== null) {
            throw ValidationException::withMessages([$errorKey => $this->lockedMessage()]);
        }
        if ($this->otp->reachedHourlyLimit($email)) {
            throw ValidationException::withMessages([$errorKey => setting('otp_hourly_error', 'Bạn đã yêu cầu nhiều mã. Vui lòng thử lại sau ít phút.')]);
        }

        $wait = $this->otp->secondsUntilResend($session);
        $last = OtpCode::query()->where('test_session_id', $session->id)->latest('id')->value('email_hash');
        $sameEmail = $last === Respondent::hashOf($email);
        if ($wait > 0 && (! $sameEmailOnly || $sameEmail)) {
            throw ValidationException::withMessages([$errorKey => str_replace('{giay}', (string) $wait, (string) setting('otp_wait_error', 'Vui lòng chờ {giay} giây rồi gửi lại mã.'))]);
        }
    }

    private function sendCode(Request $request, TestSession $session, string $email, string $errorKey): RedirectResponse
    {
        $code = $this->otp->issue($session, $email);
        if ($code === null) {
            throw ValidationException::withMessages([$errorKey => setting('otp_send_error', 'Chưa gửi được mã. Bạn kiểm tra lại email hoặc thử lại sau ít phút.')]);
        }

        // Máy chạy thử: hiện mã trên màn hình để thử nhanh. Máy chủ thật (APP_ENV=production) không bao giờ hiện.
        if (app()->isLocal() && config('app.debug')) {
            $request->session()->put(self::DEV_CODE_KEY, $code);
        }

        return redirect()->route('report.code');
    }

    private function lockedMessage(): string
    {
        return str_replace('{phut}', (string) $this->otp->setting('otp_lock_minutes'),
            (string) setting('otp_locked', 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau {phut} phút.'));
    }
}
