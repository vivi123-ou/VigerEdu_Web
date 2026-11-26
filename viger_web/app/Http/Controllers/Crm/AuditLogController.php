<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Làm gì:   Trang "Nhật ký hệ thống": ai làm thao tác nhạy cảm gì, lúc nào, từ IP nào. Chỉ xem, không sửa / xoá.
 * Ai gọi:   route crm.audit.index (quyền settings.manage).
 * Dữ liệu:  đọc crm_audit_logs (ghi bởi CrmAuditLog::record trong các controller CRM).
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'action' => ['nullable', Rule::in(array_keys(CrmAuditLog::ACTIONS))],
            'user' => ['nullable', 'integer'],
            'date' => ['nullable', Rule::in(['today', '7d', '30d'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $logs = CrmAuditLog::with('user')
            ->when($filters['action'] ?? null, fn ($q, $v) => $q->where('action', $v))
            ->when($filters['user'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('description', 'like', "%{$v}%")->orWhere('ip', $v)))
            ->when($filters['date'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', match ($v) {
                'today' => now()->startOfDay(),
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
            }))
            ->latest('created_at')->latest('id')
            ->paginate(30)->withQueryString();

        return view('crm::audit.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => CrmAuditLog::ACTIONS,
            'users' => User::whereIn('id', CrmAuditLog::whereNotNull('user_id')->distinct()->select('user_id'))
                ->orderBy('full_name')->get()->mapWithKeys(fn (User $u) => [$u->id => $u->displayName()]),
            'failedToday' => CrmAuditLog::where('action', 'login_failed')->where('created_at', '>=', now()->startOfDay())->count(),
        ]);
    }
}
