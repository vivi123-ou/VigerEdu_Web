<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CrmSetting;
use App\Models\Lead;
use App\Models\LeadActivity;

class DashboardController extends Controller
{
    /** Lead đang trong quá trình chăm sóc (chưa chốt / chưa loại) */
    private const OPEN_STATUSES = ['new', 'contacted', 'consulting', 'potential'];

    public function __invoke()
    {
        $byStatus = Lead::visibleTo(auth()->user(), true)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $byStatus->sum();

        $thisMonth = Lead::visibleTo(auth()->user(), true)->where('created_at', '>=', now()->startOfMonth())->count();
        $lastMonth = Lead::visibleTo(auth()->user(), true)->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->startOfMonth()])->count();

        $stats = [
            [
                'label' => 'Tổng Leads', 'value' => $total, 'color' => 'blue', 'icon' => 'users',
                'note' => "+{$thisMonth} tháng này".($lastMonth ? ' · tháng trước '.$lastMonth : ''),
                'url' => route('crm.leads.index'),
            ],
            [
                'label' => 'Leads mới', 'value' => (int) ($byStatus['new'] ?? 0), 'color' => 'sky', 'icon' => 'plus',
                'note' => 'Chưa liên hệ',
                'url' => route('crm.leads.index', ['status' => 'new']),
            ],
            [
                'label' => 'Đang tư vấn', 'value' => (int) ($byStatus['consulting'] ?? 0), 'color' => 'amber', 'icon' => 'chat',
                'note' => 'Tiềm năng: '.(int) ($byStatus['potential'] ?? 0),
                'url' => route('crm.leads.index', ['status' => 'consulting']),
            ],
            [
                'label' => 'Đã chuyển đổi', 'value' => (int) ($byStatus['converted'] ?? 0), 'color' => 'green', 'icon' => 'check',
                'note' => 'Tỷ lệ chuyển đổi '.($total ? round(($byStatus['converted'] ?? 0) / $total * 100, 1) : 0).'%',
                'url' => route('crm.leads.index', ['status' => 'converted']),
            ],
        ];

        // Leads mới theo 6 tháng gần nhất (tính cả tháng hiện tại)
        $leadsChart = ['labels' => [], 'data' => []];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonthsNoOverflow($i);
            $leadsChart['labels'][] = 'T'.$start->month.'/'.$start->format('y');
            $leadsChart['data'][] = Lead::visibleTo(auth()->user(), true)->whereBetween('created_at', [$start, $start->copy()->endOfMonth()])->count();
        }

        $bySource = Lead::visibleTo(auth()->user(), true)->selectRaw('source, count(*) as total')->groupBy('source')->pluck('total', 'source');
        $sourceChart = [
            'labels' => array_values(Lead::SOURCES),
            'data' => array_map(fn ($key) => (int) ($bySource[$key] ?? 0), array_keys(Lead::SOURCES)),
        ];

        $newLeads = Lead::visibleTo(auth()->user())->with('assignee')->latest()->latest('id')->limit(5)->get();

        // Công việc cần xử lý — mỗi dòng dẫn tới danh sách đã lọc sẵn
        $idleDays = CrmSetting::get('idle_days');
        $tasks = [
            [
                'count' => (int) ($byStatus['new'] ?? 0),
                'text' => 'khách mới chưa liên hệ',
                'level' => 'danger',
                'url' => route('crm.leads.index', ['status' => 'new']),
            ],
            [
                'count' => Lead::visibleTo(auth()->user(), true)->whereNull('assigned_to')->whereIn('status', self::OPEN_STATUSES)->count(),
                'text' => 'khách chưa phân công tư vấn viên',
                'level' => 'warning',
                'url' => route('crm.leads.index', ['assigned_to' => 'none']),
            ],
            [
                'count' => Lead::visibleTo(auth()->user(), true)->whereIn('status', ['contacted', 'consulting', 'potential'])
                    ->whereDoesntHave('activities', fn ($q) => $q->where('created_at', '>=', now()->subDays($idleDays)))
                    ->count(),
                'text' => "khách cần chăm sóc lại ({$idleDays} ngày chưa có hoạt động)",
                'level' => 'info',
                'url' => null,
            ],
        ];

        $activities = LeadActivity::with(['lead', 'user'])->whereHas('lead', fn ($l) => $l->visibleTo(auth()->user()))->latest()->latest('id')->limit(6)->get();

        $appointments = Appointment::upcoming()->whereHas('lead', fn ($l) => $l->visibleTo(auth()->user(), true))->with(['lead', 'consultant'])->orderBy('scheduled_at')->limit(5)->get();
        $overdueAppointments = Appointment::overdue()->whereHas('lead', fn ($l) => $l->visibleTo(auth()->user(), true))->count();

        return view('crm::dashboard', compact(
            'total', 'stats', 'leadsChart', 'sourceChart', 'newLeads', 'tasks', 'activities',
            'appointments', 'overdueAppointments'
        ));
    }
}
