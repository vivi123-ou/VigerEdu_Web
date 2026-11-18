<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Làm gì:   Thêm header bảo mật cho mọi trang: chống nhúng trang vào web lạ (trừ /embed đã tự khai tên miền được nhúng),
 *           chống trình duyệt đoán sai loại file, không gửi đường dẫn đầy đủ sang web khác, tắt camera / micro / vị trí,
 *           bắt buộc HTTPS (HSTS) khi chạy HTTPS; bỏ header lộ phiên bản PHP.
 * Ai gọi:   bootstrap/app.php (middleware toàn cục).
 * Dữ liệu:  không.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }
        $headers->remove('X-Powered-By');

        $csp = (string) $headers->get('Content-Security-Policy');
        if (! str_contains($csp, 'frame-ancestors')) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        }
        $headers->set('X-Content-Type-Options', 'nosniff', false);
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()', false);
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        return $response;
    }
}
