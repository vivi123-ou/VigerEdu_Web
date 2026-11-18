<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Làm gì:   Tài khoản bị khóa, hoặc vai trò bị tắt, giữa phiên sẽ bị đăng xuất ngay và đưa về trang đăng nhập.
 *           Chưa đăng nhập thì cho qua để middleware đăng nhập (auth / Filament) xử lý.
 * Ai gọi:   khu quản trị Filament (AdminPanelProvider) và các trang quản trị cũ (alias "admin").
 * Dữ liệu:  tài khoản đang đăng nhập.
 */
class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->canAccessAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Notification::make()->danger()
                ->title('Tài khoản này đã bị khóa hoặc không có quyền vào trang quản trị.')
                ->persistent()
                ->send();

            return redirect()->route('filament.admin.auth.login');
        }

        return $next($request);
    }
}
