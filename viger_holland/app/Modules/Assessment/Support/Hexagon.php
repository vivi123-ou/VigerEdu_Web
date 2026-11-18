<?php

namespace App\Modules\Assessment\Support;

use App\Modules\Assessment\Services\ScoringEngine;

/**
 * Làm gì:   Tọa độ hình lục giác Holland (khung 300 × 300, tâm 150,150): khung, lưới giữa, trục, đa giác điểm 6 nhóm,
 *           chấm đỏ ở các nhóm được đánh dấu, nhãn "R Thực hành". Dùng chung cho báo cáo khách và báo cáo nội bộ.
 * Ai gọi:   CustomerReport (2 nhóm đầu), InternalReport (3 nhóm đầu, thêm đa giác lần làm trước).
 * Dữ liệu:  điểm trung bình 6 nhóm (1–5).
 */
final class Hexagon
{
    private const CENTER = 150;

    private const RADIUS = 110;

    /**
     * @param  array<string, array{mean: float}>  $groups
     * @param  list<string>  $marked  nhóm có chấm đỏ
     * @param  callable(string): string  $name  tên nhóm theo mã
     * @return array{outline: string, mid: string, axes: list<array{0: float, 1: float}>, polygon: string, dots: list<array{0: float, 1: float}>, labels: list<array{0: string, 1: float, 2: float, 3: string}>}
     */
    public static function make(array $groups, array $marked, callable $name): array
    {
        $outline = $mid = $axes = $dots = $labels = [];
        foreach (str_split(ScoringEngine::ORDER) as $i => $g) {
            $outline[] = self::point($i, 1);
            $mid[] = self::point($i, 0.5);
            $axes[] = self::point($i, 1);
            if (in_array($g, $marked, true)) {
                $dots[] = self::point($i, self::ratio($groups[$g]['mean'] ?? 0));
            }
            $cos = cos(deg2rad(-90 + 60 * $i));
            [$x, $y] = self::point($i, 1.12);
            $labels[] = [$g.' '.$name($g), $x, $y + ($i === 0 ? -4 : ($i === 3 ? 14 : 5)), $cos > 0.2 ? 'start' : ($cos < -0.2 ? 'end' : 'middle')];
        }

        return [
            'outline' => self::join($outline), 'mid' => self::join($mid), 'axes' => $axes,
            'polygon' => self::polygon($groups), 'dots' => $dots, 'labels' => $labels,
        ];
    }

    /**
     * Đa giác điểm 6 nhóm (dùng riêng để vẽ thêm lần làm trước).
     *
     * @param  array<string, array{mean: float}>  $groups
     */
    public static function polygon(array $groups): string
    {
        return self::join(array_map(fn (int $i) => self::point($i, self::ratio($groups[str_split(ScoringEngine::ORDER)[$i]]['mean'] ?? 0)), range(0, 5)));
    }

    private static function ratio(float $mean): float
    {
        return max(0.04, min(1, $mean / 5));
    }

    /**
     * @return array{0: float, 1: float}
     */
    private static function point(int $i, float $ratio): array
    {
        return [
            round(self::CENTER + self::RADIUS * $ratio * cos(deg2rad(-90 + 60 * $i)), 1),
            round(self::CENTER + self::RADIUS * $ratio * sin(deg2rad(-90 + 60 * $i)), 1),
        ];
    }

    /**
     * @param  list<array{0: float, 1: float}>  $points
     */
    private static function join(array $points): string
    {
        return implode(' ', array_map(fn (array $p) => $p[0].','.$p[1], $points));
    }
}
