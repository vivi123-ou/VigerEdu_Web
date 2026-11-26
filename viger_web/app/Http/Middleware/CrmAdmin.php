<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Làm gì:   Chỉ cho Quản trị viên CRM (User::isCrmAdmin) vào route; người khác nhận 403.
 * Ai gọi:   route nhóm Hệ thống của CRM (quản lý nhân viên…), middleware('crm.admin').
 * Dữ liệu:  đọc user đang đăng nhập.
 */
class CrmAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isCrmAdmin(), 403, 'Chỉ quản trị viên CRM được vào trang này.');

        return $next($request);
    }
}
