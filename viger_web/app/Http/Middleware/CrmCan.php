<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Làm gì:   Chặn route CRM theo quyền: middleware('crm.can:leads.delete'). Không có quyền => 403.
 *           Nhiều quyền cách nhau bằng "|" => chỉ cần có một (VD 'crm.can:leads.view|reports.view').
 * Ai gọi:   routes/crm.php.
 * Dữ liệu:  User::crmCan() (bảng crm_roles / crm_role_permission).
 */
class CrmCan
{
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();
        $allowed = $user && collect(explode('|', $permissions))->contains(fn ($p) => $user->crmCan($p));

        abort_unless($allowed, 403, 'Bạn không có quyền truy cập chức năng này.');

        return $next($request);
    }
}
