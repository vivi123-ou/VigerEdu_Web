<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'label',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('settings')) {
                return $default;
            }

            return Cache::remember("site_setting_{$key}", 3600, function () use ($key, $default) {
                $setting = static::where('key', $key)->first();

                return $setting?->value ?? $default;
            });
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'text', ?string $label = null): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'group' => $group,
                'type' => $type,
                'label' => $label ?? $key,
            ]
        );

        Cache::forget("site_setting_{$key}");

        return $setting;
    }

    protected static function booted(): void
    {
        static::saved(function (Setting $setting) {
            Cache::forget("site_setting_{$setting->key}");
        });

        static::deleted(function (Setting $setting) {
            Cache::forget("site_setting_{$setting->key}");
        });
    }
}
