<?php

namespace App\Modules\Respondent\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Làm gì:   Kiểm tra Cloudflare Turnstile (chống bot, chạy ẩn) cho form nhận báo cáo. Chưa có khóa (máy chạy thử) thì bỏ qua.
 *           Cloudflare không trả lời được thì cho qua (không chặn khách thật vì lỗi mạng); vẫn còn giới hạn 5 lần / phút.
 * Ai gọi:   ReportController::submit().
 * Dữ liệu:  config services.turnstile (khóa trong .env, không để trong admin).
 */
class Turnstile
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function enabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    public function passes(?string $token, ?string $ip): bool
    {
        if (! $this->enabled()) {
            return true;
        }
        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]);
        } catch (Throwable) {
            return true;
        }

        return $response->successful() ? (bool) $response->json('success') : true;
    }
}
