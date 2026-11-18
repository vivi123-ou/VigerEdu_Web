<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Nút "Xem trước": dựng trang khách với giá trị đang gõ (chưa lưu, không ghi database).
 */
final class PagePreview
{
    /**
     * @param  array<string, string>  $values
     */
    public function render(string $target, array $values): string
    {
        Setting::preview($values);

        try {
            return match ($target) {
                'embed' => view('public.embed', ['isPreview' => true])->render(),
                default => view('public.home', ['isPreview' => true])->render(),
            };
        } finally {
            // Giá trị xem trước chỉ dùng cho lần dựng trang này.
            Setting::preview([]);
        }
    }
}
