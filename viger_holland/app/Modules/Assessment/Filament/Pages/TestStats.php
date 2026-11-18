<?php

namespace App\Modules\Assessment\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Models\User;
use App\Modules\Assessment\Models\RiasecPair;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Tracking\Models\TrackingEvent;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * Làm gì:   Trang "Số liệu làm bài" (chỉ quản trị viên): con số tổng, KHÔNG có tên khách. Phễu bắt đầu → làm xong → điền form →
 *           nhập đúng mã (bỏ ngang ở bước nào), khách dừng ở trang mấy của bài, lượt làm theo ngày, cặp mã phổ biến,
 *           nguồn khách (UTM), ngành được gợi ý nhiều, chất lượng bài làm. Chọn khoảng: 7 ngày, 30 ngày, tháng này, tất cả.
 * Ai gọi:   Filament (/admin/so-lieu-lam-bai). Quyền: stats.view.
 * Dữ liệu:  test_sessions, tracking_events, riasec_pairs.
 */
class TestStats extends Page
{
    use UsesAdminMenu;

    protected static ?string $slug = 'so-lieu-lam-bai';

    protected static string $menuKey = 'stats';

    protected string $view = 'filament.pages.test-stats';

    /** @var array<string, string> */
    public const PERIODS = ['7' => '7 ngày', '30' => '30 ngày', 'thang' => 'Tháng này', 'tat-ca' => 'Tất cả'];

    #[Url(as: 'ky')]
    public string $period = '30';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && $user->hasPermission('stats.view');
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return static::menuItem()['summary'] ?? null;
    }

    protected function getHeaderActions(): array
    {
        return [static::helpAction()];
    }

    public function setPeriod(string $period): void
    {
        $this->period = array_key_exists($period, self::PERIODS) ? $period : '30';
    }

    private function from(): ?Carbon
    {
        return match ($this->period) {
            '7' => now()->subDays(6)->startOfDay(),
            'thang' => now()->startOfMonth(),
            'tat-ca' => null,
            default => now()->subDays(29)->startOfDay(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $from = $this->from();
        $sessions = TestSession::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->get(['id', 'created_at', 'answered_at', 'verified_at', 'code2', 'utm', 'reliability', 'result']);
        $ids = $sessions->pluck('id');
        $events = TrackingEvent::query()->whereIn('test_session_id', $ids)->get(['test_session_id', 'type', 'meta']);

        $started = $sessions->count();
        $finished = $sessions->whereNotNull('answered_at');
        $submitted = $events->where('type', 'form_submitted')->pluck('test_session_id')->unique()->count();
        $verified = $sessions->whereNotNull('verified_at');

        $funnel = [
            ['Bắt đầu làm bài', $started],
            ['Làm xong bài', $finished->count()],
            ['Điền thông tin', max($submitted, $verified->count())],
            ['Nhập đúng mã, xem báo cáo', $verified->count()],
        ];

        // Khách dừng giữa bài: trang cuối cùng đã xong của những lượt chưa làm xong.
        $lastPage = $events->where('type', 'quiz_page_completed')->groupBy('test_session_id')
            ->map(fn (Collection $e) => (int) $e->max(fn ($x) => $x->meta['page'] ?? 0));
        $stopped = $sessions->whereNull('answered_at')->map(fn (TestSession $s) => $lastPage[$s->id] ?? 0)->countBy()->sortKeys();

        $pairs = RiasecPair::query()->get()->mapWithKeys(fn (RiasecPair $p) => [$p->pair => $p->name]);
        $codes = $finished->filter(fn (TestSession $s) => filled($s->code2))
            ->map(fn (TestSession $s) => RiasecPair::keyFor($s->code2[0], $s->code2[1]))->countBy()->sortDesc();

        $days = $this->period === 'tat-ca' ? 30 : (int) $from->diffInDays(now()->startOfDay()) + 1;
        $byDay = collect(range($days - 1, 0))->map(function (int $ago) use ($sessions) {
            $day = now()->subDays($ago)->toDateString();

            return [
                Carbon::parse($day)->format('d/m'),
                $sessions->filter(fn ($s) => $s->created_at->toDateString() === $day)->count(),
                $sessions->filter(fn ($s) => $s->verified_at?->toDateString() === $day)->count(),
            ];
        })->all();

        $sources = $sessions->map(fn (TestSession $s) => $s->utm['utm_source'] ?? 'Trực tiếp')->countBy()->sortDesc();
        $majors = $verified->flatMap(fn (TestSession $s) => array_column($s->result['customer']['majors'] ?? [], 'name_vi'))->countBy()->sortDesc()->take(10);
        $quality = $finished->map(fn (TestSession $s) => $s->reliability ?? 'Chưa rõ')->countBy();

        return [
            'periods' => self::PERIODS,
            'funnel' => $funnel,
            'stopped' => $stopped,
            'codes' => $codes->map(fn (int $n, string $pair) => [$pair.' – '.($pairs[$pair] ?? ''), $n])->values()->all(),
            'byDay' => $byDay,
            'sources' => $sources->map(fn (int $n, string $s) => [$s, $n])->values()->all(),
            'majors' => $majors->map(fn (int $n, string $m) => [$m, $n])->values()->all(),
            'quality' => $quality->map(fn (int $n, string $q) => [$q === 'Tin cậy' ? 'Làm bài cẩn thận' : $q, $n])->values()->all(),
            'rate' => $started ? (int) round($verified->count() / $started * 100) : 0,
        ];
    }
}
