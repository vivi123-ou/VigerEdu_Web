<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Support\AdminMenu;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

/**
 * Làm gì:   Trang "Sắp có": bấm vào mục chưa làm trên menu thì giải thích phần đó sẽ làm gì và giai đoạn nào xong.
 *           Chỉ mở được mục mà người đó có quyền; mục không tồn tại hoặc không có quyền thì báo "không tìm thấy".
 * Ai gọi:   menu Filament (AdminPanelProvider), đường dẫn /admin/sap-co?muc=…
 * Dữ liệu:  config/admin_menu.php.
 */
class ComingSoon extends Page
{
    use UsesAdminMenu;

    protected static string $menuKey = 'home';

    protected static ?string $slug = 'sap-co';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.coming-soon';

    #[Url]
    public string $muc = '';

    /** @var array<string, mixed> */
    public array $item = [];

    public function mount(): void
    {
        $item = AdminMenu::find($this->muc);

        abort_unless($item !== null && AdminMenu::isComingSoon($item) && AdminMenu::allows(auth()->user(), $this->muc), 404);

        $this->item = $item;
    }

    public function getTitle(): string|Htmlable
    {
        return $this->item['label'] ?? 'Sắp có';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->item['summary'] ?? null;
    }

    protected function getHeaderActions(): array
    {
        return [static::helpAction($this->muc)];
    }
}
