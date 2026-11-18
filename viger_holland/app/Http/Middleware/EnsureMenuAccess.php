<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chặn tài khoản không được vào một mục admin (theo vai trò). Dùng: ->middleware('menu:question-sets').
 */
class EnsureMenuAccess
{
    public function handle(Request $request, Closure $next, string $menuKey): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless($user?->canAccessMenu($menuKey), 403, 'Tài khoản của bạn không có quyền vào mục này.');

        return $next($request);
    }
}
