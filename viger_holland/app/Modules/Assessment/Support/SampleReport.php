<?php

namespace App\Modules\Assessment\Support;

use App\Modules\Assessment\Models\RiasecPair;
use App\Modules\Assessment\Models\RiasecType;
use App\Modules\Assessment\Services\ScoringEngine;

/**
 * Làm gì:   Dữ liệu cho khung "Báo cáo mẫu" ở trang bắt đầu: mã ví dụ (cài đặt intro_sample_code), tên 2 nhóm,
 *           2 ngành nền của cặp mã đó và các điểm của hình lục giác minh họa. Tất cả lấy từ danh mục, không viết cứng.
 * Ai gọi:   view public.home (cả khi xem bình thường và khi admin "Sửa trang mở đầu").
 * Dữ liệu:  riasec_types, riasec_pairs + pair_major, majors; cài đặt intro_sample_code.
 */
final class SampleReport
{
    /**
     * @return array{code: string, names: list<string>, majors: list<array{name_vi: string, name_de: ?string}>, polygon: string, dots: list<array{0: float, 1: float}>, labels: list<array{0: string, 1: float, 2: float, 3: string}>}
     */
    public static function make(?string $code): array
    {
        $letters = array_values(array_unique(array_intersect(str_split(strtoupper((string) $code)), str_split(ScoringEngine::ORDER))));
        $letters = count($letters) >= 2 ? array_slice($letters, 0, 2) : ['I', 'S'];

        $names = RiasecType::query()->whereIn('code', $letters)->pluck('name', 'code');
        $pair = RiasecPair::query()->where('pair', RiasecPair::keyFor($letters[0], $letters[1]))->with('majors')->first();
        $majors = $pair?->majors->where('is_active', true)->take(2)
            ->map(fn ($major) => ['name_vi' => $major->name_vi, 'name_de' => $major->name_de])->values()->all() ?? [];

        // Hình lục giác minh họa: 2 nhóm của mã nổi bật, các nhóm khác ở mức trung bình.
        $center = [150, 150];
        $radius = 110;
        $points = $dots = $labels = [];
        foreach (str_split(ScoringEngine::ORDER) as $i => $group) {
            $angle = deg2rad(-90 + 60 * $i);
            $value = match ($group) {
                $letters[0] => 0.92, $letters[1] => 0.85, default => 0.45
            };
            $point = [round($center[0] + $radius * $value * cos($angle), 1), round($center[1] + $radius * $value * sin($angle), 1)];
            $points[] = implode(',', $point);
            if (in_array($group, $letters, true)) {
                $dots[] = $point;
            }
            $labels[] = [$group, round($center[0] + ($radius + 22) * cos($angle), 1), round($center[1] + ($radius + 22) * sin($angle) + 8, 1),
                cos($angle) > 0.2 ? 'start' : (cos($angle) < -0.2 ? 'end' : 'middle')];
        }

        return [
            'code' => implode('', $letters),
            'names' => array_values(array_filter([$names[$letters[0]] ?? null, $names[$letters[1]] ?? null])),
            'majors' => $majors,
            'polygon' => implode(' ', $points),
            'dots' => $dots,
            'labels' => $labels,
        ];
    }

    /**
     * Khung lục giác ngoài cùng (6 đỉnh).
     */
    public static function outline(): string
    {
        return collect(range(0, 5))
            ->map(fn (int $i) => round(150 + 110 * cos(deg2rad(-90 + 60 * $i)), 1).','.round(150 + 110 * sin(deg2rad(-90 + 60 * $i)), 1))
            ->implode(' ');
    }
}
