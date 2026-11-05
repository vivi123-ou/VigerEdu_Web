<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chỉ tài khoản đang hoạt động, có vai trò hợp lệ mới vào được /admin.
 * Tài khoản bị khóa giữa phiên sẽ bị đăng xuất ngay.
 */
class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $user->canAccessAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['username' => 'Tài khoản này đã bị khóa hoặc không có quyền vào trang quản trị.']);
        }

        return $next($request);
    }
}
