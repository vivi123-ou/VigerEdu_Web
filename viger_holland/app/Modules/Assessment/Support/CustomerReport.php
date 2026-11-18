<?php

namespace App\Modules\Assessment\Support;

use App\Modules\Assessment\Models\CodeOverride;
use App\Modules\Assessment\Models\Major;
use App\Modules\Assessment\Models\Occupation;
use App\Modules\Assessment\Models\RiasecPair;
use App\Modules\Assessment\Models\RiasecType;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Models\ThirdLetter;
use App\Modules\Assessment\Services\ScoringEngine;

/**
 * Làm gì:   Dữ liệu cho báo cáo đầy đủ của khách (bản External) từ kết quả đã lưu của lượt làm bài:
 *           mã khách được thấy (2 chữ, 3 chữ khi đủ điều kiện), giải thích từng nhóm và khi hai nhóm kết hợp,
 *           hình lục giác, mức độ quan tâm 6 nhóm (nhãn chữ, không hiện điểm số), ngành gợi ý kèm lý do và nghề tham khảo.
 *           Không có họ tên, không có % , không có mã 3 chữ khi khách chưa đủ điều kiện.
 * Ai gọi:   ReportController::show().
 * Dữ liệu:  test_sessions.result, riasec_types, riasec_pairs, third_letters, code_overrides, majors, occupations; cài đặt report_*.
 */
final class CustomerReport
{
    /**
     * @return array<string, mixed>
     */
    public static function make(TestSession $session): array
    {
        $result = $session->result;
        $code = (string) $result['customer']['code'];
        $letters = str_split($code);
        $groups = $result['groups'];
        $order = $result['order'];
        $types = RiasecType::query()->get()->keyBy('code');
        $name = fn (string $g): string => (string) ($types[$g]->name ?? $g);
        $pair = RiasecPair::query()->where('pair', RiasecPair::keyFor($letters[0], $letters[1]))->first();
        $distance = ScoringEngine::hexDistance($letters[0], $letters[1]);

        $fill = fn (?string $text): string => strtr((string) $text, [
            '{nhom1}' => $name($letters[0]), '{nhom2}' => $name($letters[1]), '{ma}' => $code,
        ]);

        $explain = [];
        foreach (array_slice($letters, 0, 2) as $i => $g) {
            $explain[] = [
                'heading' => $fill(setting($i === 0 ? 'report_first_heading' : 'report_second_heading', $i === 0 ? 'Nhóm nổi bật nhất' : 'Nhóm thứ hai')).' – '.$name($g).' ('.$g.')',
                'text' => $types[$g]->description ?? setting('group_desc_'.$g),
            ];
        }

        $third = null;
        if (count($letters) === 3) {
            $letter = ThirdLetter::query()->where('code', $letters[2])->first();
            $override = CodeOverride::query()->where('code', $code)->first();
            $third = [
                'heading' => $fill(setting('report_third_heading', 'Chữ thứ ba')).' – '.$name($letters[2]).' ('.$letters[2].')',
                // Ưu tiên: đoạn riêng của đúng mã 3 chữ (code_overrides) → đoạn "Chữ thứ ba" của nhóm → câu mẫu trong cài đặt.
                'text' => $override?->interpretation ?: $letter?->description ?: strtr((string) setting('report_third_text', ''), [
                    '{nhom3}' => $name($letters[2]),
                    '{chuyen_sau}' => (string) ($letter?->specialty_vi ?: $letter?->specialty_de),
                    '{ky_nang}' => (string) ($letter?->skills ?? ''),
                ]),
            ];
        }

        $combine = $pair?->description ?: $fill(setting('report_relation_'.$distance));

        $scale = [];
        foreach ($order as $i => $g) {
            $scale[] = [
                'code' => $g, 'name' => $name($g), 'band' => $groups[$g]['band'],
                'width' => (int) round(max(0, min(5, $groups[$g]['mean'])) / 5 * 100), 'top' => $i < 2,
            ];
        }

        return [
            'code' => $code,
            'reportCode' => strtoupper(substr(str_replace('-', '', $session->uuid), 0, 8)),
            'names' => array_map($name, array_slice($letters, 0, 2)),
            'opening' => $fill(setting('report_opening')),
            'explain' => $explain,
            'third' => $third,
            'combine' => $combine,
            'hexagon' => Hexagon::make($groups, array_slice($letters, 0, 2), $name),
            'scale' => $scale,
            'ticks' => array_column(TestConfig::get('bands') ?? [], 'label'),
            'majors' => self::majors($result['customer']['majors'] ?? [], implode(' – ', array_map($name, array_slice($letters, 0, 2))), $fill),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows  ngành khách được thấy (đã lọc lúc chấm)
     * @return list<array{name_vi: string, name_de: ?string, routes: ?string, label: string, level: int, why: string, occupations: list<string>}>
     */
    private static function majors(array $rows, string $pairName, callable $fill): array
    {
        $models = Major::query()->whereIn('code', array_column($rows, 'id'))->with('occupations')->get()->keyBy('code');
        $perMajor = max(0, (int) setting('report_occupations_per_major', '3'));

        return array_map(function (array $row) use ($models, $pairName, $fill, $perMajor): array {
            $isPair = ($row['source'] ?? null) === 'pair';
            $major = $models[$row['id']] ?? null;

            return [
                'name_vi' => (string) $row['name_vi'],
                'name_de' => $row['name_de'] ?? null,
                // Loại trường (Uni, HAW…) chỉ hiện khi ngành đã kiểm tra nguồn chính thức; dữ liệu chưa kiểm tra có ghi chú nội bộ như "Cần tra".
                'routes' => $major?->is_verified ? $major->routes : null,
                'label' => $isPair ? (string) setting('result_pair_label', 'Gợi ý chính') : (string) $row['label'],
                'level' => $isPair ? 1 : (str_contains(mb_strtolower((string) $row['label']), 'nhiều') ? 2 : 3),
                'why' => $isPair
                    ? strtr((string) setting('report_why_pair', 'Ngành nền của cặp {cap}.'), ['{cap}' => $pairName])
                    : $fill(setting('report_why_catalog', 'Mã Holland của ngành gần với sở thích của bạn.')),
                'occupations' => $major
                    ? $major->occupations->where('is_active', true)->take($perMajor)
                        ->map(fn (Occupation $o) => self::occupationName($o))->filter()->values()->all()
                    : [],
            ];
        }, $rows);
    }

    /**
     * Tên nghề cho khách: "Tiếng Việt (Tiếng Đức)", ví dụ "Nhà nghiên cứu tâm lý học (Psychologieforscher)" (Vân chốt 18/11).
     * Chưa có tên tiếng Việt thì dùng tên tiếng Đức / tiếng Anh.
     */
    public static function occupationName(Occupation $occupation): string
    {
        if (filled($occupation->name_vi)) {
            return $occupation->name_vi.(filled($occupation->name_de) ? ' ('.$occupation->name_de.')' : '');
        }

        return (string) ($occupation->name_de ?: $occupation->name_en);
    }
}
