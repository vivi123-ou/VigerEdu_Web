<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Cài đặt dạng key/value, có cache. Đọc bằng helper setting('key', 'mặc định').
 */
#[Fillable(['key', 'value', 'group'])]
class Setting extends Model
{
    public const CACHE_KEY = 'holland.settings';

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * Toàn bộ cài đặt [key => value], lưu cache cho tới khi có thay đổi.
     *
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            try {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return static::query()->pluck('value', 'key')->all();
            } catch (Throwable) {
                return [];
            }
        });
    }

    /**
     * Giá trị tạm thời cho nút "Xem trước" (chỉ trong request hiện tại, không lưu).
     *
     * @var array<string, string|null>
     */
    private static array $overrides = [];

    /**
     * @param  array<string, string|null>  $values
     */
    public static function preview(array $values): void
    {
        static::$overrides = $values;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = array_key_exists($key, static::$overrides) ? static::$overrides[$key] : (static::allCached()[$key] ?? null);

        return ($value === null || $value === '') ? $default : $value;
    }

    public static function put(string $key, mixed $value, string $group = 'general'): self
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
