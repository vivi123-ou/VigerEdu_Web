<?php

namespace App\Support;

/**
 * Đọc / ghi danh sách lựa chọn dạng chữ mà người không làm IT gõ được:
 *   "Nhãn = giá trị" (hoặc chỉ "Nhãn" khi giá trị giống nhãn).
 * Trên admin: mỗi dòng một lựa chọn. Trong Excel (tab CAU_HOI): ngăn cách bằng " | ".
 */
final class ChoiceList
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function parse(?string $text, string $separator = "\n"): array
    {
        $parts = $separator === "\n"
            ? preg_split('/\r\n|\r|\n/', (string) $text)
            : explode($separator, (string) $text);

        $choices = [];

        foreach ($parts ?: [] as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            $position = mb_strrpos($part, '=');

            if ($position === false) {
                $label = $value = $part;
            } else {
                $label = trim(mb_substr($part, 0, $position));
                $value = trim(mb_substr($part, $position + 1));
                $label = $label === '' ? $value : $label;
                $value = $value === '' ? $label : $value;
            }

            $choices[] = ['value' => $value, 'label' => $label];
        }

        return $choices;
    }

    /**
     * Thang điểm (giá trị phải là số): [{value: số, label}]. Trả null nếu có giá trị không phải số.
     *
     * @return list<array{value: int|float, label: string}>|null
     */
    public static function parseScale(?string $text, string $separator = "\n"): ?array
    {
        $scale = [];

        foreach (self::parse($text, $separator) as $choice) {
            $raw = str_replace(',', '.', $choice['value']);

            if (! is_numeric($raw)) {
                return null;
            }

            $number = (float) $raw;
            $scale[] = ['value' => floor($number) === $number ? (int) $number : $number, 'label' => $choice['label']];
        }

        return $scale;
    }

    /**
     * @param  list<array<string, mixed>>|null  $choices
     */
    public static function format(?array $choices, string $separator = "\n"): string
    {
        return collect($choices ?? [])
            ->map(function (array $choice): string {
                $value = (string) ($choice['value'] ?? '');
                $label = (string) ($choice['label'] ?? $value);

                return $label === $value ? $label : $label.' = '.$value;
            })
            ->implode($separator);
    }

    /**
     * Hai danh sách có cùng giá trị và nhãn không (so sánh không phân biệt kiểu số / chữ).
     *
     * @param  list<array<string, mixed>>|null  $a
     * @param  list<array<string, mixed>>|null  $b
     */
    public static function same(?array $a, ?array $b): bool
    {
        return self::format($a) === self::format($b);
    }
}
