<?php

namespace App\Support;

use App\Filament\Pages\ComingSoon;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Làm gì:   Đọc menu trang quản trị từ config/admin_menu.php (tên, nhóm, quyền, hướng dẫn, "Sắp có") và trả đường dẫn của từng mục.
 * Ai gọi:   AdminPanelProvider (menu Filament), trait UsesAdminMenu (tên trang Filament), layout các trang cũ, User::canAccessMenu().
 * Dữ liệu:  chỉ đọc file cấu hình, không đụng cơ sở dữ liệu.
 */
class AdminMenu
{
    /**
     * Tất cả mục, theo thứ tự trong cấu hình; mỗi mục có thêm 'key' và 'sort'.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function items(): Collection
    {
        $index = 0;

        return collect(config('admin_menu.items'))
            ->map(function (array $item, string $key) use (&$index): array {
                return $item + ['key' => $key, 'sort' => ++$index, 'group' => null, 'permission' => null];
            })
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $key): ?array
    {
        return self::items()->firstWhere('key', $key);
    }

    /**
     * Các nhóm theo thứ tự, mỗi nhóm kèm các mục của nó (bỏ Trang chủ).
     *
     * @return list<array{title: string, items: list<array<string, mixed>>}>
     */
    public static function groups(): array
    {
        $items = self::items();

        return collect(config('admin_menu.groups'))
            ->map(fn (string $title) => ['title' => $title, 'items' => $items->where('group', $title)->values()->all()])
            ->all();
    }

    /**
     * Người này có được vào mục không: theo mã quyền ghi ở mục (null = ai vào được trang quản trị cũng thấy).
     */
    public static function allows(?User $user, string $key): bool
    {
        $item = self::find($key);

        if ($user === null || $item === null || ! $user->canAccessAdmin()) {
            return false;
        }

        return $item['permission'] === null || $user->hasPermission($item['permission']);
    }

    /**
     * Mục chưa làm (có giai đoạn) → trang "Sắp có".
     */
    public static function isComingSoon(array $item): bool
    {
        return filled($item['stage'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function url(array $item): string
    {
        return match (true) {
            self::isComingSoon($item) => ComingSoon::getUrl(['muc' => $item['key']]),
            isset($item['page']) => $item['page']::getUrl(),
            default => route($item['route'], $item['params'] ?? []),
        };
    }

    /**
     * Trang đầu khu quản trị, dùng cho nút "Về trang quản trị" ở các trang cũ.
     */
    public static function homeUrl(): string
    {
        return route('filament.admin.pages.dashboard');
    }

    /**
     * Các ô lối tắt ở Trang chủ quản trị cho người đang đăng nhập (tối đa 6 ô): việc dùng được ngay đứng trước, mục "Sắp có" sau.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function shortcutsFor(User $user): Collection
    {
        return self::items()
            ->filter(fn (array $item) => ($item['shortcut'] ?? false) && self::allows($user, $item['key']))
            ->sortBy(fn (array $item) => [self::isComingSoon($item) ? 1 : 0, $item['sort']])
            ->take(6)
            ->values();
    }
}
