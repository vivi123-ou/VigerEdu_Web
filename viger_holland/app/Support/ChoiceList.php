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
     * Lựa chọn của câu Holland chọn 1 / chọn nhiều: "Nhãn = R:1" (nhóm R, 1 điểm), "Nhãn = R" (1 điểm), "Nhãn" (không tính điểm).
     * Trả null nếu có phần sau dấu = không đúng dạng.
     *
     * @return list<array{value: string, label: string, group: string|null, points: int|float}>|null
     */
    public static function parseScoredChoices(?string $text, string $separator = "\n"): ?array
    {
        $choices = [];

        foreach (self::parse($text, $separator) as $index => $choice) {
            $group = null;
            $points = 0;

            if ($choice['value'] !== $choice['label']) {
                if (! preg_match('/^([RIASEC])\s*(?::\s*(\d+(?:[.,]\d+)?))?$/iu', $choice['value'], $match)) {
                    return null;
                }
                $group = mb_strtoupper($match[1]);
                $number = isset($match[2]) ? (float) str_replace(',', '.', $match[2]) : 1.0;
                $points = floor($number) === $number ? (int) $number : $number;
            }

            $choices[] = ['value' => (string) ($index + 1), 'label' => $choice['label'], 'group' => $group, 'points' => $points];
        }

        return $choices;
    }

    /**
     * @param  list<array<string, mixed>>|null  $choices
     */
    public static function formatScoredChoices(?array $choices, string $separator = "\n"): string
    {
        return collect($choices ?? [])
            ->map(fn (array $choice) => filled($choice['group'] ?? null) && (float) ($choice['points'] ?? 0) > 0
                ? $choice['label'].' = '.$choice['group'].':'.$choice['points']
                : (string) $choice['label'])
            ->implode($separator);
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
