<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Đọc menu trái của trang quản trị từ config/admin_menu.php.
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
     * Các mục chưa làm (hiện trang "Đang xây dựng").
     *
     * @return list<string>
     */
    public static function pendingKeys(): array
    {
        return self::items()->whereNotNull('stage')->pluck('key')->values()->all();
    }

    public static function url(array $item): string
    {
        return isset($item['route']) ? route($item['route'], $item['params'] ?? []) : route('admin.section', $item['key']);
    }
}
