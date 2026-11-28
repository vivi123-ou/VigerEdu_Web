<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Đường dẫn theo ngôn ngữ: tiếng Việt không có tiền tố (/ve-chung-toi),
 * tiếng Anh có tiền tố /en và slug tiếng Anh (/en/about-us).
 *
 * Bảng SEGMENTS là nguồn duy nhất: dùng để khai báo route, dịch link trong view/admin
 * và tìm trang tương ứng khi bấm nút chuyển ngôn ngữ.
 */
class LocalizedUrl
{
    /**
     * Đoạn đường dẫn tiếng Việt => tiếng Anh. Đoạn không có ở đây (slug bài viết...) giữ nguyên.
     *
     * @var array<string, string>
     */
    public const SEGMENTS = [
        've-chung-toi' => 'about-us',
        'giao-duc-tai-duc' => 'study-in-germany',
        'tong-quan' => 'overview',
        'he-thong-giao-duc' => 'education-system',
        'nghien-cuu-va-ung-dung' => 'research-and-applied-sciences',
        'chi-phi-va-co-hoi' => 'costs-and-opportunities',
        'co-hoi-nghe-nghiep' => 'career-opportunities',
        'tu-van' => 'consulting',
        'quy-trinh-tu-van' => 'consulting-process',
        'du-hoc-dai-hoc' => 'bachelor-studies',
        'du-hoc-cao-hoc' => 'master-studies',
        'chuong-trinh-hoc' => 'study-programs',
        'hieu-ve-duc' => 'understanding-germany',
        'cuoc-song-o-duc' => 'life-in-germany',
        'lien-he' => 'contact',
        'bai-viet' => 'posts',
        'trang' => 'pages',
    ];

    public static function primaryLocale(): string
    {
        return config('app.supported_locales')[0];
    }

    /** Tiền tố URL của ngôn ngữ: '' cho tiếng Việt, 'en' cho tiếng Anh. */
    public static function prefix(string $locale): string
    {
        return $locale === static::primaryLocale() ? '' : $locale;
    }

    /**
     * Đường dẫn route (không tiền tố) của ngôn ngữ $locale từ đường dẫn tiếng Việt.
     * Vd. path('/giao-duc-tai-duc/tong-quan', 'en') => '/study-in-germany/overview'.
     */
    public static function path(string $vietnamesePath, string $locale): string
    {
        if ($locale === static::primaryLocale()) {
            return '/'.ltrim($vietnamesePath, '/');
        }

        $segments = array_map(
            fn (string $segment) => static::SEGMENTS[$segment] ?? $segment,
            array_filter(explode('/', trim($vietnamesePath, '/')), 'strlen'),
        );

        return '/'.implode('/', $segments);
    }

    /**
     * URL đầy đủ theo ngôn ngữ (mặc định: ngôn ngữ đang chọn) từ đường dẫn tiếng Việt.
     * Link ngoài (http, mailto:, tel:, #...) và link đã có tiền tố ngôn ngữ giữ nguyên.
     */
    public static function to(?string $path, ?string $locale = null): string
    {
        $path = (string) $path;
        $locale ??= app()->getLocale();

        $internalPath = static::internalPath($path);

        if ($internalPath === null) {
            return $path;
        }

        [$pathOnly, $suffix] = static::splitSuffix($internalPath);

        // Đã là link có tiền tố ngôn ngữ (vd. admin nhập /en/contact) => giữ nguyên
        if (static::localeOfPath($pathOnly) !== static::primaryLocale()) {
            return url($pathOnly).$suffix;
        }

        $prefix = static::prefix($locale);
        $localized = static::path($pathOnly, $locale);

        return url(trim($prefix.$localized, '/') ?: '/').$suffix;
    }

    /** Ngôn ngữ của một đường dẫn: /en/... => en, còn lại => ngôn ngữ chính. */
    public static function localeOfPath(string $path): string
    {
        $first = explode('/', trim($path, '/'))[0];

        foreach (config('app.supported_locales') as $locale) {
            if ($locale !== static::primaryLocale() && $first === $locale) {
                return $locale;
            }
        }

        return static::primaryLocale();
    }

    /**
     * URL của trang hiện tại ở ngôn ngữ $locale (cho nút chuyển ngôn ngữ).
     * Vd. /en/study-in-germany/overview?page=2 => /giao-duc-tai-duc/tong-quan?page=2.
     */
    public static function switchTo(string $locale, ?string $currentUrl = null): string
    {
        $internalPath = static::internalPath($currentUrl ?? request()->getRequestUri()) ?? '/';
        [$pathOnly, $suffix] = static::splitSuffix($internalPath);

        return static::to(static::toVietnamesePath($pathOnly), $locale).$suffix;
    }

    /** Đưa đường dẫn của bất kỳ ngôn ngữ nào về đường dẫn tiếng Việt (bỏ tiền tố, dịch ngược slug). */
    public static function toVietnamesePath(string $path): string
    {
        $locale = static::localeOfPath($path);
        $segments = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));

        if ($locale === static::primaryLocale()) {
            return '/'.implode('/', $segments);
        }

        $reverse = array_flip(static::SEGMENTS);
        $segments = array_map(fn (string $segment) => $reverse[$segment] ?? $segment, array_slice($segments, 1));

        return '/'.implode('/', $segments);
    }

    /**
     * Phần đường dẫn trong website (kèm query/hash) của link, null nếu là link ngoài.
     * Nhận cả URL đầy đủ của chính website (vd. từ url(...) / route(...)).
     */
    private static function internalPath(string $url): ?string
    {
        if ($url === '' || Str::startsWith($url, ['#', 'mailto:', 'tel:', 'javascript:', '//'])) {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            $appUrl = url('/');

            if (! Str::startsWith($url, $appUrl)) {
                return null;
            }

            $url = Str::after($url, $appUrl);
        }

        return '/'.ltrim($url, '/');
    }

    /** @return array{0: string, 1: string} [đường dẫn, phần ?query#hash] */
    private static function splitSuffix(string $path): array
    {
        $position = strcspn($path, '?#');

        return [substr($path, 0, $position) ?: '/', substr($path, $position)];
    }
}
