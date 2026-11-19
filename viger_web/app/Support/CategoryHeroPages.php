<?php

namespace App\Support;

/**
 * Banner đầu trang (hero) của các trang con trong một menu (vd. Giáo dục tại Đức, Tư vấn).
 * Key trong bảng settings có dạng "{PREFIX}_{trang}_{trường}", vd. education_overview_heading.
 *
 * Lớp con khai báo PREFIX, PAGES, DEFAULTS và TRANSLATIONS.
 */
abstract class CategoryHeroPages extends SiteContent
{
    /** Tiền tố key và group trong bảng settings. */
    public const PREFIX = '';

    /**
     * Các trang con: id => [tên trong admin, đường dẫn, ảnh banner mặc định].
     *
     * @var array<string, array{label: string, url: string, image: string}>
     */
    public const PAGES = [];

    /** Trường chữ của mỗi banner (được dịch); ảnh "_image" dùng chung mọi ngôn ngữ. */
    public const TEXT_FIELDS = ['page_title', 'pretitle', 'heading', 'description'];

    public static function key(string $page, string $field): string
    {
        return static::PREFIX."_{$page}_{$field}";
    }

    /**
     * Nội dung banner của một trang con theo ngôn ngữ đang chọn.
     *
     * @return array{page_title: string, pretitle: string, heading: string, description: string, image: string}
     */
    public static function hero(string $page): array
    {
        $hero = [];
        foreach (static::TEXT_FIELDS as $field) {
            $hero[$field] = (string) static::get(static::key($page, $field));
        }

        $hero['image'] = (string) static::imageUrl(static::get(static::key($page, 'image')), static::PAGES[$page]['image']);

        return $hero;
    }
}
