<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Nhóm tab theo ngôn ngữ (config('app.supported_locales')) cho các cột spatie/laravel-translatable.
 *
 * Mỗi tab dựng các field có tên "{cột}.{locale}", ví dụ "title.vi", "title.en".
 * Ngôn ngữ đầu tiên là ngôn ngữ chính (dùng cho các field bắt buộc / tạo slug).
 *
 *     TranslatableTabs::make(fn (string $locale, bool $isPrimary) => [
 *         TextInput::make("title.{$locale}")->required($isPrimary),
 *     ])
 */
class TranslatableTabs
{
    /** @var array<string, string> */
    public const LOCALE_LABELS = [
        'vi' => 'Tiếng Việt',
        'en' => 'English',
    ];

    /**
     * @param  Closure(string $locale, bool $isPrimary): array<Component>  $fields
     */
    public static function make(Closure $fields, string $label = 'Ngôn ngữ'): Tabs
    {
        $locales = config('app.supported_locales');

        return Tabs::make($label)
            ->tabs(array_map(
                fn (string $locale): Tab => Tab::make(self::LOCALE_LABELS[$locale] ?? strtoupper($locale))
                    ->key("locale-{$locale}")
                    ->schema($fields($locale, $locale === $locales[0])),
                $locales,
            ))
            ->columnSpanFull();
    }
}
