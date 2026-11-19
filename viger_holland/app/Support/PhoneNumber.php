<?php

namespace App\Support;

/**
 * Chuẩn hóa số điện thoại Việt Nam theo từ điển dữ liệu: bỏ khoảng trắng / dấu chấm / gạch, +84 → 0.
 */
final class PhoneNumber
{
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9+]/', '', $phone) ?? '';

        if (str_starts_with($digits, '+84')) {
            $digits = '0'.substr($digits, 3);
        } elseif (str_starts_with($digits, '84') && strlen($digits) === 11) {
            $digits = '0'.substr($digits, 2);
        }

        return str_replace('+', '', $digits);
    }

    /**
     * Số di động / cố định VN sau chuẩn hóa: 10 chữ số, bắt đầu bằng 0.
     */
    public static function isValid(?string $phone): bool
    {
        return preg_match('/^0\d{9}$/', (string) self::normalize($phone)) === 1;
    }
}
