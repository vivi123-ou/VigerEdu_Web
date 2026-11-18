<?php

namespace App\Modules\Respondent\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Models\AdminActivity;
use App\Models\User;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Respondent\Support\InternalReport;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

/**
 * Làm gì:   Báo cáo NỘI BỘ của một lần làm bài (mẫu admin-filament.html màn hình 3), mở từ "Tra cứu báo cáo khách".
 *           Quản trị viên thấy thêm "In / PDF" và "Sao chép cho AI" (Vân chốt 17/11). Mỗi lần mở được ghi vào nhật ký.
 *           Mã lượt làm bài không đúng / chưa nhập đúng mã email → báo không tìm thấy (404).
 * Ai gọi:   Filament (/admin/bao-cao-noi-bo?ma=…), nút "Đọc báo cáo đầy đủ". Quyền: reports.lookup.
 * Dữ liệu:  InternalReport::make(); admin_activities.
 */
class InternalReportPage extends Page
{
    use UsesAdminMenu;

    protected static ?string $slug = 'bao-cao-noi-bo';

    protected static string $menuKey = 'lookup';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.internal-report';

    #[Url]
    public string $ma = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && $user->hasPermission('reports.lookup');
    }

    public function mount(): void
    {
        $session = $this->session();
        abort_if($session === null, 404);

        AdminActivity::record(AdminActivity::ACTION_VIEW, 'test_sessions', $session->id,
            'Đọc báo cáo nội bộ (mã báo cáo '.InternalReport::code($session).')');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Báo cáo của '.($this->session()?->respondent?->full_name ?? 'khách');
    }

    public function getBreadcrumbs(): array
    {
        return [LookupReports::getUrl() => static::getNavigationLabel(), 'Báo cáo nội bộ'];
    }

    protected function getHeaderActions(): array
    {
        return [static::helpAction()];
    }

    public function isAdmin(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['r' => InternalReport::make($this->session()), 'isAdmin' => $this->isAdmin()];
    }

    private function session(): ?TestSession
    {
        if (! preg_match('/^[0-9a-f-]{36}$/', $this->ma)) {
            return null;
        }

        return TestSession::query()->where('uuid', $this->ma)->whereNotNull('verified_at')->with('respondent')->first();
    }
}
