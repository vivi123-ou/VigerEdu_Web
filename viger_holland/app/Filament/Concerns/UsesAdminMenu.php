<?php

namespace App\Filament\Concerns;

use App\Support\AdminMenu;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

/**
 * Làm gì:   Cho trang / resource Filament lấy tên menu, nhóm, biểu tượng, thứ tự và câu mô tả từ config/admin_menu.php,
 *           và tạo nút "?" vàng mở hướng dẫn từng bước của trang. Đổi tên mục chỉ cần sửa file cấu hình.
 * Ai gọi:   QuestionResource, TestSettings, UserResource, RoleResource, Dashboard, ComingSoon (khai báo static $menuKey).
 * Dữ liệu:  chỉ đọc cấu hình.
 */
trait UsesAdminMenu
{
    /**
     * @return array<string, mixed>
     */
    public static function menuItem(): array
    {
        return AdminMenu::find(static::$menuKey) ?? [];
    }

    public static function getNavigationLabel(): string
    {
        return static::menuItem()['label'] ?? static::$menuKey;
    }

    public static function getNavigationGroup(): ?string
    {
        return static::menuItem()['group'] ?? null;
    }

    public static function getNavigationIcon(): ?string
    {
        return static::menuItem()['icon'] ?? null;
    }

    public static function getNavigationSort(): ?int
    {
        return static::menuItem()['sort'] ?? null;
    }

    /**
     * Nút "?" vàng ở đầu trang: mở hộp "Trang này dùng để làm gì?" với các bước làm.
     */
    public static function helpAction(?string $key = null): Action
    {
        $item = AdminMenu::find($key ?? static::$menuKey) ?? [];

        return Action::make('help')
            ->label('Hướng dẫn')
            ->tooltip('Trang này dùng để làm gì?')
            ->icon(Heroicon::QuestionMarkCircle)
            ->iconButton()
            ->size('lg')
            ->color('warning')
            ->modalHeading('Trang này dùng để làm gì?')
            ->modalContent(new HtmlString(view('admin.partials.help-content', ['help' => $item['help'] ?? []])->render()))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Đã hiểu')
            ->modalWidth('lg');
    }
}
