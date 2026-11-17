<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Làm gì:   Bảng điều khiển: số liệu nhanh về thư viện câu hỏi, cài đặt và lượt làm bài.
 * Ai gọi:   AdminPanelProvider (trang đầu /admin). Quyền: dashboard.view.
 * Dữ liệu:  qua các widget trong app/Filament/Widgets.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Bảng điều khiển';

    protected ?string $subheading = 'Xem nhanh tình trạng thư viện câu hỏi và lượt làm bài. Menu bên trái chỉ hiện các mục vai trò của bạn được dùng.';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && $user->hasPermission('dashboard.view');
    }
}
