<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Làm gì:   Trang "Khách hàng đã chuyển đổi" — khách có trạng thái converted, kèm ngày chuyển đổi
 *           và số ngày từ lúc tiếp nhận tới lúc chốt; thẻ tổng hợp ở đầu trang.
 * Ai gọi:   route crm.converted.index (menu Quản lý khách hàng → Khách hàng đã chuyển đổi).
 * Dữ liệu:  đọc leads, lead_activities. Ngày chuyển đổi = lần gần nhất lịch sử ghi
 *           "Đổi trạng thái: … → Đã chuyển đổi." (LeadController@update tự ghi); chưa có thì dùng updated_at.
 */
class ConvertedController extends Controller
{
    public const PERIODS = [
        'this_month' => 'Tháng này',
        'last_month' => 'Tháng trước',
        '90d' => '90 ngày qua',
        'this_year' => 'Năm nay',
    ];

    private const PER_PAGE = 20;

    /** Mẫu nội dung lịch sử khi chuyển sang "Đã chuyển đổi" */
    private static function convertedLogPattern(): string
    {
        return '%→ '.Lead::STATUSES['converted'][0].'.%';
    }

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'source', 'assigned_to', 'period']);
        $period = array_key_exists($filters['period'] ?? '', self::PERIODS) ? $filters['period'] : null;

        $query = Lead::visibleTo($request->user())
            ->where('status', 'converted')
            ->filter(collect($filters)->except('period')->all())
            ->withMax(['activities as converted_log_at' => fn (Builder $q) => $q->where('content', 'like', self::convertedLogPattern())], 'created_at')
            ->when($period, fn (Builder $q) => $this->applyPeriod($q, $period));

        // Tổng hợp trên toàn bộ tập đang lọc (không chỉ trang hiện tại)
        $all = (clone $query)->get(['id', 'created_at', 'updated_at', 'source']);
        $days = $all->map(fn (Lead $l) => $l->created_at->diffInDays(self::convertedAt($l)))->filter(fn ($d) => $d >= 0);
        $totalLeads = Lead::visibleTo($request->user())->count();

        $summary = [
            'total' => $all->count(),
            'this_month' => $all->filter(fn (Lead $l) => self::convertedAt($l)->gte(now()->startOfMonth()))->count(),
            'rate' => $totalLeads ? round(Lead::visibleTo($request->user())->where('status', 'converted')->count() / $totalLeads * 100, 1) : 0,
            'avg_days' => $days->isNotEmpty() ? (int) round($days->avg()) : null,
            'by_source' => collect(Lead::SOURCES)->map(fn ($label, $key) => $all->where('source', $key)->count())->filter(),
        ];

        $customers = $query
            ->with('assignee')
            ->orderByRaw('coalesce(converted_log_at, leads.updated_at) desc')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('crm::converted.index', [
            'customers' => $customers,
            'filters' => $filters,
            'summary' => $summary,
            'consultants' => $request->user()->assignableUsers(),
        ]);
    }

    /** Ngày chuyển đổi của một lead (đã nạp converted_log_at). */
    public static function convertedAt(Lead $lead): Carbon
    {
        return $lead->converted_log_at ? Carbon::parse($lead->converted_log_at) : $lead->updated_at;
    }

    /** Lọc theo khoảng thời gian chuyển đổi (dựa trên lịch sử; lead chưa có lịch sử thì dùng updated_at). */
    private function applyPeriod(Builder $q, string $period): void
    {
        [$from, $to] = match ($period) {
            'this_month' => [now()->startOfMonth(), now()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            '90d' => [now()->subDays(90), now()],
            'this_year' => [now()->startOfYear(), now()],
        };

        $q->where(fn (Builder $w) => $w
            ->whereHas('activities', fn (Builder $a) => $a
                ->where('content', 'like', self::convertedLogPattern())
                ->whereBetween('created_at', [$from, $to]))
            ->orWhere(fn (Builder $n) => $n
                ->whereDoesntHave('activities', fn (Builder $a) => $a->where('content', 'like', self::convertedLogPattern()))
                ->whereBetween('updated_at', [$from, $to])));
    }
}
