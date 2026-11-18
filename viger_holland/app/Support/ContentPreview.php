<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Làm gì:   Giữ tạm (10 phút) nội dung đang gõ, chưa lưu, để nút "Xem trước" mở trang khách ở thẻ mới.
 *           Mỗi lần bấm một mã ngẫu nhiên, chỉ đúng người bấm mở được.
 * Ai gọi:   ContentSettingsPage, IntroPage (nút "Xem trước"); ContentPreviewController (mở trang xem trước).
 * Dữ liệu:  cache, không ghi database.
 */
final class ContentPreview
{
    /**
     * @param  array<string, string>  $values
     */
    public static function put(string $target, array $values): string
    {
        $token = Str::random(40);
        Cache::put(self::key($token), ['target' => $target, 'values' => $values, 'user_id' => auth()->id()], now()->addMinutes(10));

        return route('admin.content.preview', $token);
    }

    /**
     * @return array{target: string, values: array<string, string>}|null
     */
    public static function get(string $token): ?array
    {
        $preview = Cache::get(self::key($token));

        return is_array($preview) && $preview['user_id'] === auth()->id() ? $preview : null;
    }

    private static function key(string $token): string
    {
        return 'content-preview:'.hash('sha256', $token);
    }
}
