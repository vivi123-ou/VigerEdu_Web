<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\NoIndex;
use App\Models\User;
use App\Support\AdminMenu;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Làm gì:   Khu quản trị /admin dựng bằng Filament: đăng nhập, menu, màu và logo, các trang của từng module.
 *           Trang khách vẫn là Blade thuần, không dùng Filament.
 * Ai gọi:   Laravel (bootstrap/providers.php).
 * Dữ liệu:  logo, màu, tên đơn vị lấy từ bảng settings (sửa ở "Giao diện & chân trang"); quyền từ bảng roles / permissions.
 */
class AdminPanelProvider extends PanelProvider
{
    /** Các module có trang quản trị: thư mục => namespace. Thêm module mới thì thêm một dòng. */
    private const MODULES = [
        'Assessment' => 'App\\Modules\\Assessment\\Filament',
        'Access' => 'App\\Modules\\Access\\Filament',
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
            ->colors(fn () => ['primary' => Color::hex((string) setting('primary_color', '#c1121f'))])
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Bài trắc nghiệm'),
                NavigationGroup::make('Nội dung trang'),
                NavigationGroup::make('Hệ thống'),
            ])
            ->navigationItems($this->contentPageItems())
            ->userMenuItems([
                Action::make('public-site')->label('Xem trang test như khách')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn () => route('home'), shouldOpenInNewTab: true),
            ])
            ->pages([Dashboard::class])
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
     * Các trang nội dung làm từ trước (sửa trang giới thiệu, nhúng, giao diện, thư viện ảnh) chưa chuyển sang Filament:
     * vẫn hiện trong menu, bấm vào mở trang cũ. Sẽ chuyển dần ở H6.
     *
     * @return list<NavigationItem>
     */
    private function contentPageItems(): array
    {
        $icons = [
            'intro' => Heroicon::OutlinedPencilSquare,
            'embed' => Heroicon::OutlinedCodeBracket,
            'appearance' => Heroicon::OutlinedSwatch,
            'media' => Heroicon::OutlinedPhoto,
        ];

        return collect(AdminMenu::items())
            ->filter(fn (array $item) => isset($item['route'], $icons[$item['key']]))
            ->values()
            ->map(fn (array $item, int $index) => NavigationItem::make($item['label'])
                ->group('Nội dung trang')
                ->icon($icons[$item['key']])
                ->sort($index + 1)
                ->url(fn () => AdminMenu::url($item))
                ->visible(fn () => auth()->user() instanceof User && auth()->user()->canAccessMenu($item['key'])))
            ->all();
    }
}
