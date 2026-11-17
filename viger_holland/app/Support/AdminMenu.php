<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Đọc danh sách các trang nội dung cũ (chưa chuyển sang Filament) từ config/admin_menu.php.
 */
class AdminMenu
{
    /**
     * @return list<array{title?: string, items: list<array<string, mixed>>}>
     */
    public static function groups(): array
    {
        return config('admin_menu');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function items(): Collection
    {
        return collect(self::groups())->flatMap(fn (array $group) => $group['items']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $key): ?array
    {
        return self::items()->firstWhere('key', $key);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function url(array $item): string
    {
        return route($item['route'], $item['params'] ?? []);
    }

    /**
     * Trang đầu khu quản trị (Bảng điều khiển của Filament), dùng cho nút "Về trang quản trị" ở các trang cũ.
     */
    public static function homeUrl(): string
    {
        return route('filament.admin.pages.dashboard');
    }
}
