<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Làm gì:   Trang chủ quản trị: lời chào, các ô lối tắt (chỉ việc người đó được làm), số liệu nhanh (chỉ người có quyền).
 * Ai gọi:   AdminPanelProvider (trang đầu /admin). Ai đăng nhập được trang quản trị cũng vào được.
 * Dữ liệu:  qua các widget trong app/Filament/Widgets.
 */
class Dashboard extends BaseDashboard
{
    use UsesAdminMenu;

    protected static string $menuKey = 'home';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin();
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    protected function getHeaderActions(): array
    {
        return [static::helpAction()];
    }
}
