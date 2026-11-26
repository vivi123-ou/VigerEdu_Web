<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Làm gì:   Trang một nguồn khách (Website VIGER, Holland, AI Chatbot, Nguồn khác): tình trạng kết nối kênh,
 *           số liệu, khách theo tháng, phễu trạng thái, khách mới nhất, lời nhắn gần đây.
 * Ai gọi:   route crm.sources.show (menu Nguồn khách hàng → …).
 * Dữ liệu:  chỉ đọc dữ liệu phía CRM: leads, lead_activities, crm_intake_events; cấu hình kênh config('crm.api_channels').
 *           Không đọc bảng của web chính (contact_messages) — hai bên chỉ nối qua API.
 */
class SourceController extends Controller
{
    /** Mô tả ngắn từng nguồn (hiện dưới tiêu đề) */
    private const DESCRIPTIONS = [
        'website' => 'Khách để lại thông tin qua form liên hệ trên viger.edu.vn — web chính gửi sang CRM qua API.',
        'holland' => 'Khách làm trắc nghiệm Holland tại holland.viger.edu.vn và đồng ý nhận tư vấn.',
        'chatbot' => 'Khách trò chuyện với AI Chatbot và để lại thông tin liên hệ.',
        'other' => 'Khách nhập tay, giới thiệu, sự kiện hoặc các kênh chưa phân loại.',
    ];

    public function show(string $source)
    {
        abort_unless(array_key_exists($source, Lead::SOURCES), 404);

        $leads = Lead::visibleTo(auth()->user(), true)->where('source', $source);
        $byStatus = (clone $leads)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $byStatus->sum();

        $thisMonth = (clone $leads)->where('created_at', '>=', now()->startOfMonth())->count();
        $lastMonth = (clone $leads)->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->startOfMonth()])->count();
        $converted = (int) ($byStatus['converted'] ?? 0);

        $stats = [
            'total' => $total,
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'converted' => $converted,
            'rate' => $total ? round($converted / $total * 100, 1) : 0,
            'new' => (int) ($byStatus['new'] ?? 0),
            'share' => ($all = Lead::visibleTo(auth()->user(), true)->count()) ? round($total / $all * 100, 1) : 0,
        ];

        // Khách mới theo 6 tháng gần nhất
        $chart = ['labels' => [], 'data' => []];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonthsNoOverflow($i);
            $chart['labels'][] = 'T'.$start->month.'/'.$start->format('y');
            $chart['data'][] = (clone $leads)->whereBetween('created_at', [$start, $start->copy()->endOfMonth()])->count();
        }

        // Phễu: số khách mỗi trạng thái
        $funnel = collect(Lead::STATUSES)->map(fn ($meta, $key) => [
            'label' => $meta[0],
            'count' => (int) ($byStatus[$key] ?? 0),
            'pct' => $total ? round(($byStatus[$key] ?? 0) / $total * 100) : 0,
        ]);

        return view('crm::sources.show', [
            'source' => $source,
            'label' => Lead::SOURCES[$source],
            'description' => self::DESCRIPTIONS[$source] ?? '',
            'stats' => $stats,
            'chart' => $chart,
            'funnel' => $funnel,
            'channel' => $this->channel($source),
            'holland' => $source === 'holland' ? $this->hollandInsights() : null,
            'byHour' => $source === 'chatbot' ? $this->leadsByHour($source) : null,
            'creators' => $source === 'other' ? $this->creators($source) : null,
            'recentLeads' => auth()->user()->crmCan('leads.view')
                ? (clone $leads)->with('assignee')->latest()->latest('id')->limit(8)->get()
                : collect(),
            'messages' => LeadActivity::where('type', 'form')->whereHas('lead', fn ($l) => $l->visibleTo(auth()->user()))
                ->whereHas('lead', fn ($q) => $q->where('source', $source))
                ->with('lead:id,full_name')
                ->latest()->latest('id')->limit(5)->get(),
        ]);
    }

    /**
     * Thống kê riêng trang Holland (khách nguồn Holland đã có kết quả):
     * phân bố cặp mã 2 chữ, điểm trung bình từng nhóm (quy về thang 1–5), độ tin cậy bài làm.
     */
    private function hollandInsights(): array
    {
        $columns = array_map(fn ($l) => 'holland_'.strtolower($l), array_keys(Lead::HOLLAND_TYPES));

        $leads = Lead::visibleTo(auth()->user(), true)->where('source', 'holland')
            ->where(function ($q) use ($columns) {
                $q->whereNotNull('holland_code');
                foreach ($columns as $c) {
                    $q->orWhereNotNull($c);
                }
            })
            ->get(array_merge(['id', 'holland_code', 'holland_result'], $columns));

        $pairs = $leads->map(fn (Lead $l) => mb_substr((string) $l->hollandCode(), 0, 2))->filter()
            ->countBy()->sortDesc()->take(8);

        // Điểm 0–100 quy ngược về thang 1–5 để cùng đơn vị với báo cáo Holland
        $averages = collect(Lead::HOLLAND_TYPES)->map(function ($t, $l) use ($leads) {
            $vals = $leads->pluck('holland_'.strtolower($l))->filter(fn ($v) => $v !== null);

            return $vals->isNotEmpty() ? round($vals->avg() / 100 * 4 + 1, 2) : null;
        });

        return [
            'with_result' => $leads->count(),
            'pairs' => $pairs,
            'averages' => $averages,
            'reliability' => $leads->map(fn (Lead $l) => $l->holland_result['reliability'] ?? null)->filter()->countBy(),
        ];
    }

    /**
     * Số khách để lại thông tin theo giờ trong ngày (0–23h), 90 ngày gần nhất.
     * Gom bằng PHP để chạy giống nhau trên MySQL và SQLite.
     *
     * @return array<int, int>
     */
    private function leadsByHour(string $source): array
    {
        $hours = array_fill(0, 24, 0);

        Lead::visibleTo(auth()->user(), true)->where('source', $source)
            ->where('created_at', '>=', now()->subDays(90))
            ->pluck('created_at')
            ->each(function ($at) use (&$hours) {
                $hours[(int) $at->format('G')]++;
            });

        return $hours;
    }

    /**
     * Ai nhập khách của nguồn này vào CRM: người thực hiện dòng lịch sử đầu tiên "CRM tiếp nhận…"
     * (Lead::created tự ghi kèm user đang đăng nhập). Trả về [tên => số khách], nhiều nhất trước.
     */
    private function creators(string $source): Collection
    {
        $leadIds = Lead::visibleTo(auth()->user(), true)->where('source', $source)->pluck('id');

        return LeadActivity::query()
            ->whereIn('lead_id', $leadIds)
            ->where('type', 'system')
            ->where('content', 'like', 'CRM tiếp nhận%')
            ->with('user')
            ->get()
            ->unique('lead_id')
            ->countBy(fn (LeadActivity $a) => $a->user?->displayName() ?? 'Không rõ / tự động')
            ->sortDesc();
    }

    /** Tình trạng kênh API tương ứng (null nếu nguồn chưa có kênh tự động, VD "Nguồn khác"). */
    private function channel(string $source): ?array
    {
        $cfg = collect(config('crm.api_channels'))->firstWhere('code', $source);

        if (! $cfg) {
            return null;
        }

        $events = DB::table('crm_intake_events')->where('channel', $source);

        return [
            'enabled' => (bool) ($cfg['enabled'] ?? false),
            'configured' => ! empty($cfg['token_hash']),
            'last_at' => ($last = (clone $events)->max('created_at')) ? Carbon::parse($last) : null,
            'count_30d' => (clone $events)->where('created_at', '>=', now()->subDays(30))->count(),
            'count_all' => (clone $events)->count(),
        ];
    }
}
