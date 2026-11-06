<?php

use App\Models\Setting;
use App\Support\HtmlSanitizer;

if (! function_exists('setting')) {
    /**
     * Đọc một cài đặt do admin sửa (bảng settings), trả về $default nếu chưa có.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('rich_text')) {
    /**
     * Chữ có định dạng (từ trình soạn thảo) đã lọc an toàn, in bằng {!! rich_text(...) !!}.
     */
    function rich_text(?string $html): string
    {
        return HtmlSanitizer::clean($html);
    }
}

if (! function_exists('media_url')) {
    /**
     * Đường dẫn ảnh: ảnh trong thư viện (storage/media/…), ảnh có sẵn (img/…) hoặc link ngoài (https://…).
     */
    function media_url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return preg_match('#^https?://#i', $path) ? $path : asset($path);
    }
}
