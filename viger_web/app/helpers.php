<?php

use App\Support\LocalizedUrl;

if (! function_exists('localized_url')) {
    /**
     * URL theo ngôn ngữ đang chọn từ đường dẫn tiếng Việt.
     * Vd. ở chế độ tiếng Anh: localized_url('/lien-he') => https://.../en/contact.
     */
    function localized_url(?string $path, ?string $locale = null): string
    {
        return LocalizedUrl::to($path, $locale);
    }
}
