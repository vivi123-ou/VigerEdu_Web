<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Support\AdminMenu;
use Filament\Widgets\Widget;

/**
 * Làm gì:   Lời chào và các ô lối tắt ở Trang chủ quản trị; mỗi người chỉ thấy ô của việc mình được làm.
 * Ai gọi:   Dashboard (Filament tự tìm widget trong app/Filament/Widgets).
 * Dữ liệu:  config/admin_menu.php (mục có 'shortcut'), quyền của người đang đăng nhập.
 */
class Shortcuts extends Widget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.shortcuts';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return [
            'name' => $user->name,
            'shortcuts' => AdminMenu::shortcutsFor($user)->map(fn (array $item) => $item + ['url' => AdminMenu::url($item)]),
        ];
    }
}
