<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Arr;
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

    /**
     * Bản dịch mặc định cho ngôn ngữ phụ: ['en' => [key => giá trị]].
     * Key có mặt ở đây là key đa ngôn ngữ; bản dịch admin nhập được lưu ở setting "{key}_{locale}".
     * Key không có ở đây (ảnh, đường dẫn, con số...) dùng chung cho mọi ngôn ngữ.
     *
     * @var array<string, array<string, mixed>>
     */
    public const TRANSLATIONS = [];

    /**
     * Key dạng danh sách (Repeater) đa ngôn ngữ => các trường cần dịch trong mỗi mục.
     * Các trường khác (con số, ảnh, tên người...) lấy từ bản tiếng Việt theo cùng vị trí.
     *
     * @var array<string, list<string>>
     */
    public const TRANSLATABLE_ITEM_FIELDS = [];

    /**
     * Lấy giá trị theo ngôn ngữ (mặc định: ngôn ngữ đang chọn trên website).
     * Ngôn ngữ chính: giá trị đã lưu, rỗng => mặc định.
     * Ngôn ngữ phụ: bản dịch đã lưu, rỗng => bản dịch mặc định, không có => bản tiếng Việt.
     */
    public static function get(string $key, ?string $locale = null): mixed
    {
        $value = static::stored($key) ?? (static::DEFAULTS[$key] ?? null);
        $locale ??= app()->getLocale();

        if ($locale === static::primaryLocale() || ! static::isTranslatable($key, $locale)) {
            return $value;
        }

        $translation = static::translation($key, $locale);

        if (in_array($key, static::JSON_KEYS, true)) {
            return static::mergeTranslatedItems($key, $value, $translation);
        }

        return filled($translation) ? $translation : $value;
    }

    /**
     * Toàn bộ nội dung, dạng mảng key => value.
     *
     * @return array<string, mixed>
     */
    public static function all(?string $locale = null): array
    {
        $data = [];
        foreach (array_keys(static::DEFAULTS) as $key) {
            $data[$key] = static::get($key, $locale);
        }

        return $data;
    }

    public static function primaryLocale(): string
    {
        return config('app.supported_locales')[0];
    }

    public static function isTranslatable(string $key, string $locale = 'en'): bool
    {
        return array_key_exists($key, static::TRANSLATIONS[$locale] ?? []);
    }

    /** Key trong bảng settings lưu bản dịch, vd. home_hero_heading_en. */
    public static function translationKey(string $key, string $locale): string
    {
        return "{$key}_{$locale}";
    }

    /** Bản dịch đã lưu của key (rỗng => bản dịch mặc định). */
    public static function translation(string $key, string $locale): mixed
    {
        $value = static::stored(static::translationKey($key, $locale), in_array($key, static::JSON_KEYS, true));

        return $value ?? (static::TRANSLATIONS[$locale][$key] ?? null);
    }

    /** Giá trị trong bảng settings (JSON => mảng), rỗng => null. */
    protected static function stored(string $settingKey, ?bool $isJson = null): mixed
    {
        $value = Setting::get($settingKey);

        if (($isJson ?? in_array($settingKey, static::JSON_KEYS, true)) && is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : null;
        }

        return filled($value) ? $value : null;
    }

    /**
     * Ghép bản dịch vào danh sách gốc theo vị trí: chỉ thay các trường được dịch, giữ nguyên số/ảnh/tên.
     *
     * @param  array<int, mixed>|null  $items
     * @return array<int, mixed>|null
     */
    protected static function mergeTranslatedItems(string $key, ?array $items, mixed $translatedItems): ?array
    {
        if (! is_array($items)) {
            return $items;
        }

        $fields = static::TRANSLATABLE_ITEM_FIELDS[$key] ?? [];
        $translatedItems = is_array($translatedItems) ? array_values($translatedItems) : [];

        return array_map(
            function (mixed $item, int $index) use ($fields, $translatedItems) {
                $translated = $translatedItems[$index] ?? null;

                if (! is_array($item) || ! is_array($translated)) {
                    return $item;
                }

                return array_replace($item, array_filter(Arr::only($translated, $fields), 'filled'));
            },
            array_values($items),
            array_keys(array_values($items)),
        );
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
