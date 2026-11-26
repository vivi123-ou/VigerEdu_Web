<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\LeadActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Làm gì:   Trang "Lịch sử hoạt động" — dòng thời gian chung của mọi khách (ghi chú, cuộc gọi, lịch hẹn,
 *           đổi trạng thái…), lọc theo loại / người thực hiện / thời gian / khách.
 * Ai gọi:   route crm.activities.index (menu Quản lý tư vấn → Lịch sử hoạt động).
 * Dữ liệu:  đọc lead_activities (+ leads, users). Chỉ đọc, không sửa.
 */
class ActivityController extends Controller
{
    public const PERIODS = [
        'today' => 'Hôm nay',
        'yesterday' => 'Hôm qua',
        '7d' => '7 ngày qua',
        '30d' => '30 ngày qua',
        'this_month' => 'Tháng này',
    ];

    private const PER_PAGE = 40;

    public function index(Request $request)
    {
        $type = array_key_exists($request->query('type'), LeadActivity::TYPES) ? $request->query('type') : null;
        $period = array_key_exists($request->query('period'), self::PERIODS) ? $request->query('period') : null;
        $user = $request->query('user');
        $q = trim((string) $request->query('q'));

        // Bộ lọc chung (trừ loại) — dùng để đếm số hoạt động mỗi loại trên tab
        $base = LeadActivity::query()
            ->whereHas('lead', fn (Builder $l) => $l->visibleTo($request->user()))
            ->when($user === 'system', fn (Builder $b) => $b->whereNull('user_id'))
            ->when($user && $user !== 'system', fn (Builder $b) => $b->where('user_id', $user))
            ->when($period, fn (Builder $b) => $this->applyPeriod($b, $period))
            ->when($q !== '', fn (Builder $b) => $b->whereHas('lead', fn (Builder $l) => $l
                ->where('full_name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('student_name', 'like', "%{$q}%")));

        $byType = (clone $base)->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');
        $counts = ['all' => (int) $byType->sum()] + collect(LeadActivity::TYPES)->map(fn ($t, $k) => (int) ($byType[$k] ?? 0))->all();

        $activities = (clone $base)
            ->when($type, fn (Builder $b) => $b->where('type', $type))
            ->with(['lead:id,full_name,phone,student_name', 'user'])
            ->latest()->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('crm::activities.index', [
            'activities' => $activities,
            'groups' => $activities->getCollection()->groupBy(fn (LeadActivity $a) => $a->created_at->toDateString()),
            'type' => $type,
            'period' => $period,
            'user' => $user,
            'q' => $q,
            'counts' => $counts,
            'consultants' => $request->user()->assignableUsers(),
        ]);
    }

    private function applyPeriod(Builder $b, string $period): void
    {
        match ($period) {
            'today' => $b->where('created_at', '>=', now()->startOfDay()),
            'yesterday' => $b->whereBetween('created_at', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()]),
            '7d' => $b->where('created_at', '>=', now()->subDays(7)),
            '30d' => $b->where('created_at', '>=', now()->subDays(30)),
            'this_month' => $b->where('created_at', '>=', now()->startOfMonth()),
        };
    }
}
