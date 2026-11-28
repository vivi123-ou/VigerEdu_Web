<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Đặt ngôn ngữ hiển thị.
     *
     * - Route trang công khai khai báo ngôn ngữ qua tham số (middleware 'locale:en'): URL quyết định
     *   ngôn ngữ (/en/... là tiếng Anh) và ghi nhớ vào session.
     * - Trang khác (đăng nhập...): dùng ngôn ngữ đã ghi nhớ trong session, không có => config('app.locale').
     */
    public function handle(Request $request, Closure $next, ?string $routeLocale = null): Response
    {
        $supportedLocales = config('app.supported_locales');

        if (in_array($routeLocale, $supportedLocales, true)) {
            $request->session()->put('locale', $routeLocale);
            App::setLocale($routeLocale);

            return $next($request);
        }

        $locale = $request->session()->get('locale');

        if (in_array($locale, $supportedLocales, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
