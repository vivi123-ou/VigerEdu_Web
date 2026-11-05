<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Nội dung trang quản trị từ admin, lưu trong bảng settings.
 * Lớp con khai báo const DEFAULTS (key => giá trị mặc định) và JSON_KEYS (key lưu dạng mảng JSON).
 */
abstract class SiteContent
{
    public const DEFAULTS = [];

    /** Các key lưu dạng mảng (Repeater) -> encode JSON trong DB. */
    public const JSON_KEYS = [];

    /** Lấy giá trị đã lưu (rỗng => mặc định). */
    public static function get(string $key): mixed
    {
        $value = Setting::get($key);

        if (in_array($key, static::JSON_KEYS, true) && is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : null;
        }

        return filled($value) ? $value : (static::DEFAULTS[$key] ?? null);
    }

    /** Toàn bộ nội dung, dạng mảng key => value. */
    public static function all(): array
    {
        $data = [];
        foreach (array_keys(static::DEFAULTS) as $key) {
            $data[$key] = static::get($key);
        }

        return $data;
    }

    /** Đường dẫn ảnh: URL tuyệt đối / asset trong public (img/...) / file upload trên disk public. */
    public static function imageUrl(?string $path, ?string $fallback = null): ?string
    {
        $path = filled($path) ? $path : $fallback;

        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', '/'])) {
            return $path;
        }

        if (Str::startsWith($path, 'img/')) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    /** Escape HTML, **chữ** => tô màu nhấn, xuống dòng => <br>. */
    public static function highlight(?string $text, string $color = 'var(--accent-alt)'): string
    {
        $html = e((string) $text);
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<span style="color: '.$color.'">$1</span>', $html);

        return nl2br($html, false);
    }
}
