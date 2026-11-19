<?php

namespace App\Support;

/**
 * Link do người không làm IT gõ: "viger.edu.vn/du-hoc" → "https://viger.edu.vn/du-hoc".
 * Giữ nguyên link đã có http(s)://, mailto:, tel:, đường dẫn trong trang (/…, #…).
 */
final class LinkNormalizer
{
    public static function normalize(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        if ($value === '' || preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $value)) {
            return $value;
        }

        // "www.abc.vn", "abc.vn/trang", "abc.com.vn"
        if (preg_match('#^[\p{L}0-9-]+(\.[\p{L}0-9-]+)+(:\d+)?([/?\#].*)?$#u', $value)) {
            return 'https://'.$value;
        }

        return $value;
    }
}
