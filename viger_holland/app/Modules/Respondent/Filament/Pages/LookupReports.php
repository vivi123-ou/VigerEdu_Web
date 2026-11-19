<?php

namespace App\Modules\Respondent\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Models\AdminActivity;
use App\Models\User;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Respondent\Services\ReportLookup;
use App\Modules\Respondent\Support\InternalReport;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Làm gì:   Trang "Tra cứu báo cáo khách" (mẫu admin-filament.html màn hình 2): gõ đúng số điện thoại (học sinh hoặc phụ huynh),
 *           email hoặc mã báo cáo → các lần làm bài của khách đó (mới nhất trên), bấm "Đọc báo cáo đầy đủ".
 *           Không có danh sách, gõ thiếu không gợi ý. Mỗi lần tra cứu ghi vào nhật ký (không lưu chữ đã gõ).
 *           Quá 30 lần / giờ thì báo chung chung. Trang không ghi ra các giới hạn này (Vân chốt 17/11).
 * Ai gọi:   Filament (/admin/tra-cuu-bao-cao). Quyền: reports.lookup.
 * Dữ liệu:  respondents, test_sessions (qua ReportLookup); admin_activities.
 */
class LookupReports extends Page
{
    use UsesAdminMenu;

    protected static ?string $slug = 'tra-cuu-bao-cao';

    protected static string $menuKey = 'lookup';

    protected string $view = 'filament.pages.lookup-reports';

    public string $search = '';

    public ?string $message = null;

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && $user->hasPermission('reports.lookup');
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

    public function find(ReportLookup $lookup): void
    {
        abort_unless(static::canAccess(), 404);
        $this->rows = [];
        $this->message = null;

        if (trim($this->search) === '') {
            $this->message = 'Vui lòng gõ số điện thoại, email hoặc mã báo cáo.';

            return;
        }

        $key = 'tra-cuu:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            $this->message = 'Vui lòng thử lại sau ít phút.';

            return;
        }
        RateLimiter::hit($key, 3600);

        ['type' => $type, 'sessions' => $sessions] = $lookup->find($this->search);
        AdminActivity::record(AdminActivity::ACTION_SEARCH, 'respondents', null,
            'Tra cứu báo cáo khách'.($type ? ' theo '.['email' => 'email', 'phone' => 'số điện thoại', 'code' => 'mã báo cáo'][$type] : '').' – '.$sessions->count().' kết quả');

        if ($sessions->isEmpty()) {
            $this->message = 'Không tìm thấy khách. Kiểm tra lại số điện thoại, email hoặc mã báo cáo.';

            return;
        }

        $this->rows = $sessions->map(fn (TestSession $s) => [
            'uuid' => $s->uuid,
            'name' => $s->respondent?->full_name ?? '—',
            'code3' => $s->code3,
            'reportCode' => InternalReport::code($s),
            'role' => InternalReport::roleLabel($s->respondent?->role),
            'birth' => $s->respondent?->birth_date ? substr($s->respondent->birth_date, 0, 4) : $s->respondent?->birth_year,
            'at' => $s->answered_at?->format('H:i d/m/Y'),
            'reliability' => $s->reliability,
        ])->all();
    }

    public function reportUrl(string $uuid): string
    {
        return InternalReportPage::getUrl(['ma' => $uuid]);
    }
}
