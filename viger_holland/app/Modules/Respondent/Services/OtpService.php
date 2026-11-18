<?php

namespace App\Modules\Respondent\Services;

use App\Modules\Assessment\Models\TestSession;
use App\Modules\Respondent\Mail\OtpCodeMail;
use App\Modules\Respondent\Models\OtpCode;
use App\Modules\Respondent\Models\Respondent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Làm gì:   Mã xác nhận 6 số gửi qua email để mở báo cáo đầy đủ. Mọi con số đọc từ cài đặt (nhóm "report"):
 *           hiệu lực (otp_ttl_minutes), số lần nhập sai (otp_max_attempts) rồi khóa (otp_lock_minutes),
 *           chờ gửi lại (otp_resend_seconds), số mã tối đa mỗi giờ cho một email (otp_max_per_hour, chống gửi thư rác).
 *           Khóa tính theo email, nên làm bài lượt mới cũng không mở khóa sớm được.
 * Ai gọi:   ReportController.
 * Dữ liệu:  otp_codes (chỉ lưu HMAC của mã và băm email). Không ghi email, mã vào log.
 */
class OtpService
{
    public const LENGTH = 6;

    public function setting(string $key): int
    {
        $defaults = ['otp_ttl_minutes' => 5, 'otp_max_attempts' => 5, 'otp_lock_minutes' => 30, 'otp_resend_seconds' => 60, 'otp_max_per_hour' => 5];

        return max(1, (int) setting($key, (string) $defaults[$key]));
    }

    /**
     * Email đang bị khóa đến lúc nào (null = không khóa).
     */
    public function lockedUntil(string $email): ?Carbon
    {
        $until = OtpCode::query()->where('email_hash', Respondent::hashOf($email))->where('locked_until', '>', now())->max('locked_until');

        return $until ? Carbon::parse($until) : null;
    }

    /**
     * Còn bao nhiêu giây mới được gửi lại mã cho lượt làm bài này.
     */
    public function secondsUntilResend(TestSession $session): int
    {
        $last = OtpCode::query()->where('test_session_id', $session->id)->latest('id')->first();
        if ($last === null) {
            return 0;
        }

        return max(0, $this->setting('otp_resend_seconds') - (int) $last->created_at->diffInSeconds(now()));
    }

    public function reachedHourlyLimit(string $email): bool
    {
        return OtpCode::query()->where('email_hash', Respondent::hashOf($email))
            ->where('created_at', '>', now()->subHour())->count() >= $this->setting('otp_max_per_hour');
    }

    /**
     * Tạo mã mới (mã cũ chưa dùng của lượt này hết hiệu lực) và gửi email ngay để khách nhận được liền.
     *
     * @return string|null mã vừa gửi; null khi không gửi được email (đã xóa mã, không tính lượt gửi)
     */
    public function issue(TestSession $session, string $email): ?string
    {
        $code = str_pad((string) random_int(0, 10 ** self::LENGTH - 1), self::LENGTH, '0', STR_PAD_LEFT);

        OtpCode::query()->where('test_session_id', $session->id)->whereNull('used_at')->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        $row = OtpCode::query()->create([
            'test_session_id' => $session->id,
            'email_hash' => Respondent::hashOf($email),
            'code_hash' => $this->hashCode($code),
            'expires_at' => now()->addMinutes($this->setting('otp_ttl_minutes')),
        ]);

        try {
            Mail::to($email)->send(new OtpCodeMail($code, $this->setting('otp_ttl_minutes')));
        } catch (Throwable $e) {
            $row->delete();
            report(new \RuntimeException('Không gửi được email mã xác nhận ('.class_basename($e).').'));

            return null;
        }

        return $code;
    }

    /**
     * Kiểm tra mã khách nhập.
     *
     * @return array{ok: bool, reason: 'ok'|'missing'|'locked'|'expired'|'wrong', remaining: int}
     */
    public function verify(TestSession $session, string $email, string $code): array
    {
        $max = $this->setting('otp_max_attempts');

        if ($this->lockedUntil($email) !== null) {
            return ['ok' => false, 'reason' => 'locked', 'remaining' => 0];
        }

        $row = OtpCode::query()->where('test_session_id', $session->id)->whereNull('used_at')->latest('id')->first();
        if ($row === null || $row->email_hash !== Respondent::hashOf($email)) {
            return ['ok' => false, 'reason' => 'missing', 'remaining' => $max];
        }
        if ($row->expires_at->isPast()) {
            return ['ok' => false, 'reason' => 'expired', 'remaining' => $max - $row->attempts];
        }

        if (! hash_equals($row->code_hash, $this->hashCode($code))) {
            $row->attempts++;
            if ($row->attempts >= $max) {
                $row->locked_until = now()->addMinutes($this->setting('otp_lock_minutes'));
            }
            $row->save();

            return $row->locked_until
                ? ['ok' => false, 'reason' => 'locked', 'remaining' => 0]
                : ['ok' => false, 'reason' => 'wrong', 'remaining' => $max - $row->attempts];
        }

        $row->update(['used_at' => now()]);

        return ['ok' => true, 'reason' => 'ok', 'remaining' => $max];
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', 'otp|'.$code, (string) config('app.key'));
    }
}
