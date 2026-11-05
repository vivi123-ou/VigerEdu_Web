<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    /**
     * Đọc một cài đặt do admin sửa (bảng settings), trả về $default nếu chưa có.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}
