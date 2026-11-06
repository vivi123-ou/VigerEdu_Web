<?php

use App\Models\Setting;
use App\Support\HtmlSanitizer;
use App\Support\PageEditor;
use Illuminate\Support\HtmlString;

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

if (! function_exists('ed')) {
    /**
     * Chữ lấy từ cài đặt, sửa được trực tiếp khi admin mở trang ở chế độ "Sửa trên trang".
     * Ngoài chế độ sửa: in như {{ setting($key, $default) }}.
     */
    function ed(string $key, ?string $default = null, string $label = ''): HtmlString
    {
        return PageEditor::text($key, (string) setting($key, $default), $label);
    }
}

if (! function_exists('ed_rich')) {
    /**
     * Chữ có định dạng lấy từ cài đặt (đã lọc an toàn), sửa được trực tiếp ở chế độ "Sửa trên trang".
     */
    function ed_rich(string $key, string $label = ''): HtmlString
    {
        return PageEditor::text($key, (string) setting($key), $label, rich: true);
    }
}

if (! function_exists('ed_block')) {
    /**
     * Chữ trong một khối trang giới thiệu, sửa được trực tiếp. $path ví dụ "title", "items.0.question".
     *
     * @param  array{id: string, data: array<string, mixed>}  $block
     */
    function ed_block(array $block, string $path, string $label = '', bool $rich = false): HtmlString
    {
        return PageEditor::text('block:'.$block['id'].':'.$path, (string) data_get($block['data'], $path, ''), $label, $rich);
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
