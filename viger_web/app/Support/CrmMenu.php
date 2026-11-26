<?php

namespace App\Support;

/**
 * Làm gì:   Đọc một mục menu CRM (config/crm.php → menu): tạo link và kiểm tra mục đang được chọn.
 *           Hỗ trợ 'params' cho route có tham số, VD nguồn khách: ['source' => 'website'].
 * Ai gọi:   viger_crm/partials/sidebar.blade.php, viger_crm/layouts/app.blade.php (breadcrumb).
 * Dữ liệu:  chỉ đọc config và route hiện tại.
 */
class CrmMenu
{
    /** Mục có 'permission' chỉ hiện với người có quyền đó (người khác: ẩn hẳn khỏi menu). */
    public static function visible(array $item): bool
    {
        return empty($item['permission']) || (bool) auth()->user()?->crmCan($item['permission']);
    }

    public static function url(array $item): string
    {
        return $item['route'] ? route($item['route'], $item['params'] ?? []) : '#';
    }

    public static function isActive(array $item): bool
    {
        if (! $item['route'] || ! request()->routeIs($item['active'] ?? $item['route'])) {
            return false;
        }

        // Cùng route nhưng khác tham số (VD nguồn website vs holland) => không phải mục này
        foreach ($item['params'] ?? [] as $key => $value) {
            if ((string) request()->route($key) !== (string) $value) {
                return false;
            }
        }

        return true;
    }
}
