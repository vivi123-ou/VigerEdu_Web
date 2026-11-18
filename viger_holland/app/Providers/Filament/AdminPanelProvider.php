<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Pages\ComingSoon;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\NoIndex;
use App\Models\User;
use App\Support\AdminMenu;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Làm gì:   Khu quản trị /admin dựng bằng Filament: đăng nhập, menu (theo config/admin_menu.php), màu và logo,
 *           thanh trên (xem trang khách, web chính, CRM, tên và vai trò), các trang của từng module.
 *           Trang khách vẫn là Blade thuần, không dùng Filament.
 * Ai gọi:   Laravel (bootstrap/providers.php).
 * Dữ liệu:  logo, màu, tên đơn vị, địa chỉ web chính / CRM lấy từ bảng settings; quyền từ bảng roles / permissions.
 */
class AdminPanelProvider extends PanelProvider
{
    /** Các module có trang quản trị: thư mục => namespace. Thêm module mới thì thêm một dòng. */
    private const MODULES = [
        'Assessment' => 'App\\Modules\\Assessment\\Filament',
        'Access' => 'App\\Modules\\Access\\Filament',
        'Respondent' => 'App\\Modules\\Respondent\\Filament',
        'CrmSync' => 'App\\Modules\\CrmSync\\Filament',
    ];

    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->loginRouteSlug('dang-nhap')
            ->brandName(fn () => 'Quản trị '.setting('site_name', 'Viger-Edu'))
            ->brandLogo(fn () => media_url(setting('logo_path', 'img/logo.png')))
            ->brandLogoHeight('2.25rem')
            ->favicon(fn () => media_url(setting('favicon_path', 'img/favicon.png')))
            ->colors(fn () => ['primary' => self::brandPalette((string) setting('primary_color', '#c1121f'))])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('22rem')
            ->globalSearch(false)
            ->navigationGroups(collect(config('admin_menu.groups'))->map(fn (string $group) => NavigationGroup::make($group))->all())
            ->navigationItems($this->extraNavigationItems())
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => view('filament.topbar-links'))
            ->pages([Dashboard::class, ComingSoon::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                NoIndex::class,
                EnsureAdminAccess::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        foreach (self::MODULES as $folder => $namespace) {
            $panel
                ->discoverResources(in: app_path("Modules/{$folder}/Filament/Resources"), for: "{$namespace}\\Resources")
                ->discoverPages(in: app_path("Modules/{$folder}/Filament/Pages"), for: "{$namespace}\\Pages");
        }

        return $panel;
    }

    /**
     * Mục menu không phải trang Filament: trang làm từ trước (chuyển dần ở H6) và mục "Sắp có".
     * Mỗi người chỉ thấy mục mình có quyền; mục "Sắp có" mờ đi và có nhãn.
     *
     * @return list<NavigationItem>
     */
    private function extraNavigationItems(): array
    {
        return AdminMenu::items()
            ->filter(fn (array $item) => ! isset($item['page']))
            ->map(fn (array $item) => NavigationItem::make($item['label'])
                ->group($item['group'])
                ->icon($item['icon'])
                ->sort($item['sort'])
                ->badge(AdminMenu::isComingSoon($item) ? 'Sắp có' : null, color: 'gray')
                ->url(fn () => AdminMenu::url($item))
                ->isActiveWhen(fn () => AdminMenu::isComingSoon($item)
                    ? request()->routeIs('filament.admin.pages.sap-co') && request()->query('muc') === $item['key']
                    : request()->url() === AdminMenu::url($item))
                ->visible(fn () => AdminMenu::allows(auth()->user() instanceof User ? auth()->user() : null, $item['key'])))
            ->values()
            ->all();
    }

    /**
     * Bảng màu chính từ màu thương hiệu: nút chính (sắc độ 600) dùng đúng màu thương hiệu, các sắc độ khác sáng / tối dần quanh nó.
     * Filament tự sinh bảng màu thì nút bị nhạt (hồng), trông như đang bị khóa.
     *
     * @return array<int, string>
     */
    public static function brandPalette(string $hex): array
    {
        $palette = Color::hex($hex);
        [$lightness, $chroma, $hue] = sscanf(Color::convertToOklch($hex), 'oklch(%f %f %f)');
        $shade = fn (float $deltaL, float $chromaFactor): string => sprintf('oklch(%.4f %.4f %.3f)', max(0.05, min(0.98, $lightness + $deltaL)), $chroma * $chromaFactor, $hue);

        return array_replace($palette, [
            400 => $shade(0.16, 0.80),
            500 => $shade(0.07, 0.95),
            600 => $shade(0, 1),
            700 => $shade(-0.07, 0.95),
            800 => $shade(-0.14, 0.85),
        ]);
    }
}
