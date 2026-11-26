<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Làm gì:   Tài khoản bị khoá trong lúc đang đăng nhập => đăng xuất ngay.
 *           Tài khoản đang dùng mật khẩu tạm (must_change_password) phải đổi mật khẩu trước khi dùng CRM.
 * Ai gọi:   toàn bộ route CRM cần đăng nhập, middleware('crm.password'); bỏ qua trang đổi mật khẩu và đăng xuất.
 * Dữ liệu:  đọc users.must_change_password.
 */
class CrmForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        // Tài khoản vừa bị khoá trong lúc đang đăng nhập => đăng xuất ngay
        if ($request->user() && ! $request->user()->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('crm.login')->withErrors(['username' => 'Tài khoản đã bị khoá. Vui lòng liên hệ quản trị viên.']);
        }

        if ($request->user()?->must_change_password
            && ! $request->routeIs('crm.account.password', 'crm.account.password.update', 'crm.logout')) {
            return redirect()->route('crm.account.password')
                ->with('warning', 'Bạn đang dùng mật khẩu tạm. Vui lòng đặt mật khẩu mới để tiếp tục.');
        }

        return $next($request);
    }
}
