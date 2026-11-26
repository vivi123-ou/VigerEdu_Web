<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CrmSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Làm gì:   Báo cáo "Tổng quan khách hàng" theo khoảng thời gian: chỉ số chính (so kỳ trước), khách mới / chốt
 *           theo thời gian, hiệu quả từng nguồn, chân dung khách (chương trình, học vấn, tiếng Đức, bang).
 * Ai gọi:   route crm.reports.customers (menu Báo cáo & thống kê → Tổng quan khách hàng).
 * Dữ liệu:  chỉ đọc leads, lead_activities. "Chốt trong kỳ" = lịch sử ghi "→ Đã chuyển đổi." trong kỳ
 *           (giống trang Khách hàng đã chuyển đổi).
 */
class ReportController extends Controller
{
    public const PERIODS = [
        'this_month' => 'Tháng này',
        'last_month' => 'Tháng trước',
        '90d' => '90 ngày qua',
        'this_year' => 'Năm nay',
        'custom' => 'Tự chọn',
    ];

    /** Kỳ ngắn hơn số ngày này thì biểu đồ gom theo ngày, dài hơn gom theo tháng */
    private const DAILY_MAX_DAYS = 45;

    public function customers(Request $request)
    {
        [$period, $from, $to] = $this->resolvePeriod($request);

        // Kỳ trước cùng độ dài, liền trước kỳ đang xem
        // Carbon 3: diffInDays trả số thực => so theo ngày đầu và làm tròn
        $days = (int) round($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay())) + 1;
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        $current = $this->metrics($from, $to);
        $previous = $this->metrics($prevFrom, $prevTo);

        $newLeads = Lead::visibleTo(auth()->user(), true)->whereBetween('created_at', [$from, $to])->get();
        $convertedIds = $this->convertedLeadIds($from, $to);

        return view('crm::reports.customers', [
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'prevFrom' => $prevFrom,
            'prevTo' => $prevTo,
            'current' => $current,
            'previous' => $previous,
            'trend' => $this->trend($from, $to),
            'bySource' => $this->bySource($newLeads, $convertedIds),
            'profile' => $this->profile($newLeads),
            'totalAll' => Lead::visibleTo(auth()->user(), true)->count(),
        ]);
    }

    /**
     * Báo cáo "Hiệu suất tư vấn viên" trong kỳ: khối lượng, hoạt động, lịch hẹn, khách chốt,
     * thời gian liên hệ lần đầu (trung vị), khách bị bỏ quên (đang chăm sóc, 7 ngày không tương tác).
     */
    public function consultants(Request $request)
    {
        [$period, $from, $to] = $this->resolvePeriod($request);

        $open = ['new', 'contacted', 'consulting', 'potential'];
        $contactTypes = array_merge(LeadActivity::MANUAL_TYPES, ['appointment']);
        $convertedIds = $this->convertedLeadIds($from, $to);

        // Phạm vi: Consultant chỉ thấy dòng của mình, Manager thấy thành viên nhóm
        $viewer = $request->user();
        $idleDays = CrmSetting::get('idle_days');
        $users = User::where('active', true)
            ->when($viewer->crmScope() === 'team', fn ($q) => $q->whereIn('id', $viewer->teamMemberIds()))
            ->when($viewer->crmScope() === 'own', fn ($q) => $q->whereKey($viewer->id))
            ->orderBy('full_name')->get();

        $rows = $users->map(function (User $u) use ($from, $to, $open, $contactTypes, $convertedIds, $idleDays) {
            $newAssigned = Lead::visibleTo(auth()->user(), true)->where('assigned_to', $u->id)->whereBetween('created_at', [$from, $to])->get(['id', 'created_at']);

            $activities = LeadActivity::where('user_id', $u->id)
                ->whereIn('type', LeadActivity::MANUAL_TYPES)
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

            $appts = Appointment::where('user_id', $u->id)
                ->whereBetween('scheduled_at', [$from, $to])
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

            $converted = Lead::visibleTo(auth()->user(), true)->where('assigned_to', $u->id)->whereIn('id', $convertedIds)->count();

            return [
                'user' => $u,
                'open' => Lead::visibleTo(auth()->user(), true)->where('assigned_to', $u->id)->whereIn('status', $open)->count(),
                'new_assigned' => $newAssigned->count(),
                'activities' => collect(LeadActivity::MANUAL_TYPES)->mapWithKeys(fn ($t) => [$t => (int) ($activities[$t] ?? 0)]),
                'activities_total' => (int) $activities->sum(),
                'appt_done' => (int) ($appts['done'] ?? 0),
                'appt_no_show' => (int) ($appts['no_show'] ?? 0),
                'converted' => $converted,
                'rate' => $newAssigned->count() ? round($converted / $newAssigned->count() * 100, 1) : null,
                'first_response_h' => $this->medianFirstResponseHours($newAssigned, $contactTypes),
                // Bỏ quên (N ngày, trang Cấu hình CRM): vào CRM hơn N ngày và N ngày qua không có tương tác của nhân viên
                // (dòng hệ thống như "CRM tiếp nhận…" không tính là tương tác)
                'idle' => Lead::visibleTo(auth()->user(), true)->where('assigned_to', $u->id)->whereIn('status', $open)
                    ->where('created_at', '<', now()->subDays($idleDays))
                    ->whereDoesntHave('activities', fn ($q) => $q
                        ->whereIn('type', $contactTypes)
                        ->where('created_at', '>=', now()->subDays($idleDays)))
                    ->count(),
            ];
        })
            // Ẩn người không phụ trách khách nào và không có hoạt động nào trong kỳ (VD tài khoản quản trị kỹ thuật)
            ->filter(fn ($r) => $r['open'] || $r['new_assigned'] || $r['activities_total'] || $r['converted'] || $r['appt_done'])
            ->sortByDesc(fn ($r) => [$r['converted'], $r['activities_total']])
            ->values();

        return view('crm::reports.consultants', [
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'totals' => [
                'activities' => $rows->sum('activities_total'),
                'converted' => $rows->sum('converted'),
                'idle' => $rows->sum('idle'),
                'unassigned' => Lead::visibleTo(auth()->user(), true)->whereNull('assigned_to')->whereIn('status', $open)->count(),
            ],
        ]);
    }

    /** Các giai đoạn theo thứ tự tiến lên (lost là nhánh rẽ, không nằm trong phễu) */
    private const FUNNEL = ['new', 'contacted', 'consulting', 'potential', 'converted'];

    /**
     * Báo cáo "Chuyển đổi": phễu theo nhóm khách vào CRM trong kỳ, thời gian giữa các giai đoạn,
     * tỷ lệ chốt theo nguồn / chương trình, bảng nhóm theo tháng tiếp nhận (6 tháng).
     */
    public function conversion(Request $request)
    {
        [$period, $from, $to] = $this->resolvePeriod($request);

        $cohort = Lead::visibleTo(auth()->user(), true)->whereBetween('created_at', [$from, $to])->get(['id', 'status', 'source', 'program_interest', 'created_at']);
        $reached = $this->stagesReached($cohort);
        $size = $cohort->count();

        // Phễu: số khách đã từng tới mỗi giai đoạn
        $funnel = [];
        $prev = null;
        foreach (self::FUNNEL as $stage) {
            $n = collect($reached)->filter(fn ($r) => isset($r[$stage]))->count();
            $funnel[$stage] = [
                'label' => Lead::STATUSES[$stage][0],
                'count' => $n,
                'pct' => $size ? round($n / $size * 100, 1) : 0,
                'step' => $prev === null ? null : ($prev ? round($n / $prev * 100, 1) : 0),
            ];
            $prev = $n;
        }

        // Thời gian (ngày, trung vị) từ lúc vào CRM tới lần đầu đạt giai đoạn
        $timing = collect(['contacted', 'consulting', 'converted'])->mapWithKeys(fn ($stage) => [$stage => [
            'label' => Lead::STATUSES[$stage][0],
            'days' => $this->median($cohort->map(fn (Lead $l) => isset($reached[$l->id][$stage]) && $reached[$l->id][$stage] instanceof Carbon
                ? max(0, $l->created_at->diffInHours($reached[$l->id][$stage]) / 24)
                : null)->filter(fn ($v) => $v !== null)),
        ]]);

        $breakdown = fn (string $field, ?array $labels = null) => $cohort
            ->groupBy(fn (Lead $l) => $l->{$field} ?: '—')
            ->map(fn ($group, $key) => [
                'label' => $labels[$key] ?? ($key === '—' ? 'Chưa rõ' : $key),
                'size' => $group->count(),
                'converted' => $group->filter(fn (Lead $l) => isset($reached[$l->id]['converted']))->count(),
                'lost' => $group->where('status', 'lost')->count(),
            ])
            ->map(fn ($r) => $r + ['rate' => $r['size'] ? round($r['converted'] / $r['size'] * 100, 1) : 0])
            ->sortByDesc('size');

        return view('crm::reports.conversion', [
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'size' => $size,
            'funnel' => $funnel,
            'timing' => $timing,
            'lost' => $cohort->where('status', 'lost')->count(),
            'bySource' => $breakdown('source', Lead::SOURCES),
            'byProgram' => $breakdown('program_interest'),
            'cohorts' => $this->monthlyCohorts(),
        ]);
    }

    /**
     * Với mỗi lead: các giai đoạn đã từng đạt => thời điểm đạt (Carbon), hoặc true nếu chỉ suy ra được
     * (VD trạng thái hiện tại vượt quá giai đoạn đó mà không có dòng lịch sử, như dữ liệu seed).
     *
     * @return array<int, array<string, Carbon|true>>
     */
    private function stagesReached(Collection $leads): array
    {
        $labelToStage = collect(self::FUNNEL)->mapWithKeys(fn ($s) => [Lead::STATUSES[$s][0] => $s])->all();
        $result = [];

        foreach ($leads as $l) {
            $result[$l->id] = ['new' => $l->created_at];
        }

        // Dòng lịch sử "Đổi trạng thái: A → B." cho biết thời điểm đạt B
        LeadActivity::whereIn('lead_id', $leads->pluck('id'))
            ->where('type', 'system')
            ->where('content', 'like', 'Đổi trạng thái:%')
            ->orderBy('created_at')
            ->get(['lead_id', 'content', 'created_at'])
            ->each(function (LeadActivity $a) use (&$result, $labelToStage) {
                if (preg_match('/→ (.+)\.$/u', $a->content, $m) && isset($labelToStage[$m[1]])) {
                    $result[$a->lead_id][$labelToStage[$m[1]]] ??= $a->created_at;
                }
            });

        // Trạng thái hiện tại => đã đi qua mọi giai đoạn trước đó
        foreach ($leads as $l) {
            $pos = array_search($l->status, self::FUNNEL, true);
            if ($pos === false) {
                continue;   // lost: chỉ tính các giai đoạn có trong lịch sử
            }
            foreach (array_slice(self::FUNNEL, 0, $pos + 1) as $stage) {
                $result[$l->id][$stage] ??= true;
            }
        }

        // Đạt giai đoạn sau thì coi như đã qua các giai đoạn trước (VD kéo thẳng Mới → Đang tư vấn)
        foreach ($result as $id => $stages) {
            $maxPos = max(array_map(fn ($s) => array_search($s, self::FUNNEL, true), array_keys($stages)));
            foreach (array_slice(self::FUNNEL, 0, $maxPos + 1) as $stage) {
                $result[$id][$stage] ??= true;
            }
        }

        return $result;
    }

    /** Nhóm theo tháng tiếp nhận (6 tháng gần nhất): khách vào, đến nay đã chốt / đã loại. */
    private function monthlyCohorts(): array
    {
        $rows = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonthsNoOverflow($i);
            $leads = Lead::visibleTo(auth()->user(), true)->whereBetween('created_at', [$start, $start->copy()->endOfMonth()])->get(['id', 'status', 'created_at']);
            $reached = $this->stagesReached($leads);
            $converted = collect($reached)->filter(fn ($r) => isset($r['converted']))->count();

            $rows[] = [
                'label' => 'Tháng '.$start->format('m/Y'),
                'size' => $leads->count(),
                'converted' => $converted,
                'lost' => $leads->where('status', 'lost')->count(),
                'open' => $leads->whereIn('status', ['new', 'contacted', 'consulting', 'potential'])->count(),
                'rate' => $leads->count() ? round($converted / $leads->count() * 100, 1) : 0,
            ];
        }

        return $rows;
    }

    private function median(Collection $values): ?float
    {
        $v = $values->sort()->values();
        if ($v->isEmpty()) {
            return null;
        }
        $mid = intdiv($v->count(), 2);

        return round($v->count() % 2 ? $v[$mid] : ($v[$mid - 1] + $v[$mid]) / 2, 1);
    }

    /**
     * Trung vị số giờ từ lúc khách được tiếp nhận đến lần liên hệ đầu tiên (ghi chú, cuộc gọi, lịch hẹn…).
     * Khách chưa được liên hệ không tính vào trung vị.
     */
    private function medianFirstResponseHours(Collection $leads, array $contactTypes): ?float
    {
        if ($leads->isEmpty()) {
            return null;
        }

        $first = LeadActivity::whereIn('lead_id', $leads->pluck('id'))
            ->whereIn('type', $contactTypes)
            ->selectRaw('lead_id, min(created_at) as first_at')
            ->groupBy('lead_id')
            ->pluck('first_at', 'lead_id');

        $hours = $leads
            ->filter(fn (Lead $l) => isset($first[$l->id]))
            ->map(fn (Lead $l) => max(0, $l->created_at->diffInMinutes(Carbon::parse($first[$l->id])) / 60))
            ->sort()->values();

        if ($hours->isEmpty()) {
            return null;
        }

        $mid = intdiv($hours->count(), 2);

        return round($hours->count() % 2 ? $hours[$mid] : ($hours[$mid - 1] + $hours[$mid]) / 2, 1);
    }

    /** @return array{0: string, 1: Carbon, 2: Carbon} */
    private function resolvePeriod(Request $request): array
    {
        $period = array_key_exists($request->query('period'), self::PERIODS) ? $request->query('period') : 'this_month';

        if ($period === 'custom' && ($range = $this->customRange($request->query('from'), $request->query('to')))) {
            return ['custom', ...$range];
        }

        return match ($period) {
            'last_month' => [$period, now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            '90d' => [$period, now()->subDays(89)->startOfDay(), now()->endOfDay()],
            'this_year' => [$period, now()->startOfYear(), now()->endOfDay()],
            default => ['this_month', now()->startOfMonth(), now()->endOfDay()],
        };
    }

    /**
     * Khoảng tự chọn hợp lệ, hoặc null (ngày sai định dạng, đảo ngược, dài quá 3 năm) => dùng mặc định.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function customRange(mixed $from, mixed $to): ?array
    {
        try {
            $start = Carbon::parse((string) $from)->startOfDay();
            $end = Carbon::parse((string) $to)->endOfDay();
        } catch (\Throwable) {
            return null;
        }

        return $start->lte($end) && $start->diffInDays($end) <= 1100 ? [$start, $end] : null;
    }

    private function convertedLeadIds(Carbon $from, Carbon $to): Collection
    {
        return LeadActivity::where('type', 'system')->whereHas('lead', fn ($l) => $l->visibleTo(auth()->user(), true))
            ->where('content', 'like', '%→ '.Lead::STATUSES['converted'][0].'.%')
            ->whereBetween('created_at', [$from, $to])
            ->distinct()
            ->pluck('lead_id');
    }

    /** Chỉ số chính của một kỳ */
    private function metrics(Carbon $from, Carbon $to): array
    {
        $new = Lead::visibleTo(auth()->user(), true)->whereBetween('created_at', [$from, $to])->count();
        $converted = $this->convertedLeadIds($from, $to)->count();
        $lost = LeadActivity::where('type', 'system')->whereHas('lead', fn ($l) => $l->visibleTo(auth()->user(), true))
            ->where('content', 'like', '%→ '.Lead::STATUSES['lost'][0].'.%')
            ->whereBetween('created_at', [$from, $to])
            ->distinct()->count('lead_id');

        return [
            'new' => $new,
            'converted' => $converted,
            'lost' => $lost,
            'rate' => $new ? round($converted / $new * 100, 1) : 0,
            'contacted' => Lead::visibleTo(auth()->user(), true)->whereBetween('created_at', [$from, $to])->where('status', '!=', 'new')->count(),
        ];
    }

    /** Khách mới / khách chốt theo ngày hoặc theo tháng */
    private function trend(Carbon $from, Carbon $to): array
    {
        $daily = $from->diffInDays($to) < self::DAILY_MAX_DAYS;
        $key = fn (Carbon $d) => $daily ? $d->format('Y-m-d') : $d->format('Y-m');

        $buckets = [];
        for ($d = $from->copy(); $d->lte($to); $daily ? $d->addDay() : $d->addMonthNoOverflow()->startOfMonth()) {
            $buckets[$key($d)] = ['label' => $daily ? $d->format('d/m') : 'T'.$d->month.'/'.$d->format('y'), 'new' => 0, 'converted' => 0];
        }

        Lead::visibleTo(auth()->user(), true)->whereBetween('created_at', [$from, $to])->pluck('created_at')
            ->each(function ($at) use (&$buckets, $key) {
                if (isset($buckets[$k = $key($at)])) {
                    $buckets[$k]['new']++;
                }
            });

        LeadActivity::where('type', 'system')->whereHas('lead', fn ($l) => $l->visibleTo(auth()->user(), true))
            ->where('content', 'like', '%→ '.Lead::STATUSES['converted'][0].'.%')
            ->whereBetween('created_at', [$from, $to])
            ->get(['lead_id', 'created_at'])
            ->unique('lead_id')
            ->each(function ($a) use (&$buckets, $key) {
                if (isset($buckets[$k = $key($a->created_at)])) {
                    $buckets[$k]['converted']++;
                }
            });

        return [
            'daily' => $daily,
            'labels' => array_column($buckets, 'label'),
            'new' => array_column($buckets, 'new'),
            'converted' => array_column($buckets, 'converted'),
        ];
    }

    /** Hiệu quả từng nguồn: khách mới trong kỳ, trong số đó bao nhiêu đã chốt */
    private function bySource(Collection $newLeads, Collection $convertedIds): Collection
    {
        return collect(Lead::SOURCES)->map(function ($label, $key) use ($newLeads, $convertedIds) {
            $group = $newLeads->where('source', $key);
            $converted = $group->filter(fn (Lead $l) => $l->status === 'converted' || $convertedIds->contains($l->id))->count();

            return [
                'label' => $label,
                'new' => $group->count(),
                'converted' => $converted,
                'rate' => $group->count() ? round($converted / $group->count() * 100, 1) : 0,
                'share' => $newLeads->count() ? round($group->count() / $newLeads->count() * 100) : 0,
            ];
        });
    }

    /** Chân dung khách mới trong kỳ: top giá trị của vài trường hồ sơ */
    private function profile(Collection $newLeads): array
    {
        $top = fn (string $field, int $limit = 6) => $newLeads->pluck($field)->filter()->countBy()->sortDesc()->take($limit);

        return [
            'program_interest' => ['title' => 'Chương trình quan tâm', 'items' => $top('program_interest')],
            'education_level' => ['title' => 'Trình độ học vấn', 'items' => $top('education_level')],
            'german_level' => ['title' => 'Trình độ tiếng Đức', 'items' => $top('german_level', 7)],
            'desired_state' => ['title' => 'Bang mong muốn', 'items' => $top('desired_state')],
        ];
    }
}
