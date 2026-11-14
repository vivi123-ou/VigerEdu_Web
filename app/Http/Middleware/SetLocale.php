<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Đặt ngôn ngữ hiển thị theo lựa chọn lưu trong session (route switch.language).
     * Không có hoặc không hợp lệ => dùng ngôn ngữ mặc định config('app.locale').
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (in_array($locale, config('app.supported_locales'), true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
