<?php

namespace App\Modules\Assessment\Services;

/**
 * Làm gì:   Chấm điểm và phân tích một bài làm, giữ nguyên kết quả của bản tham chiếu docs/holland_v3/HollandEngine.php:
 *           điểm trung bình 6 nhóm, xếp hạng (bằng điểm theo thứ tự R I A S E C), mã 2 / 3 chữ, khi nào khách thấy chữ thứ 3,
 *           mã thay thế khi sát điểm, quan hệ lục giác, độ nhất quán, 2 trục Prediger, kiểu hồ sơ và cờ chất lượng bài làm.
 *           Không đụng cơ sở dữ liệu; gợi ý ngành do MajorMatcher làm.
 * Ai gọi:   AssessmentService::finish(), test 8 ca của Thầy (tests/Feature/Assessment/ReferenceCasesTest.php).
 * Dữ liệu:  câu trả lời [code, group, value 1–5, ms] và cài đặt TestConfig::current(). Mọi ngưỡng đọc từ cài đặt.
 */
final class ScoringEngine
{
    public const ORDER = 'RIASEC';

    /**
     * Chỉ dùng khi có câu bỏ trống (sản phẩm hiện bắt buộc trả lời mọi câu, nên không xảy ra): số câu tối thiểu để
     * một nhóm đủ dữ liệu và số câu bỏ trống thì gắn cờ. Giữ lại để chạy đúng 8 ca tham chiếu của Thầy.
     */
    private const MIN_VALID_PER_GROUP = 4;

    private const BLANK_FLAG_TOTAL = 6;

    /**
     * @param  array<string, mixed>  $settings  TestConfig::current()
     */
    public function __construct(private readonly array $settings) {}

    public static function hexDistance(string $a, string $b): int
    {
        $d = abs(strpos(self::ORDER, $a) - strpos(self::ORDER, $b));

        return min($d, 6 - $d);
    }

    public static function relation(int $distance): string
    {
        return [0 => 'Trùng', 1 => 'Liền kề', 2 => 'Cách một', 3 => 'Đối kháng'][$distance];
    }

    /**
     * Chỉ số C (Brown & Gore 1994): mỗi vị trí cộng trọng số 3, 2, 1 nhân (3 − khoảng cách lục giác); 0–18.
     */
    public static function cIndex(string $student, string $major): int
    {
        $c = 0;
        foreach ([3, 2, 1] as $i => $weight) {
            if ($i < strlen($major) && $i < strlen($student)) {
                $c += $weight * (3 - self::hexDistance($student[$i], $major[$i]));
            }
        }

        return $c;
    }

    public function band(float $mean): string
    {
        $bands = collect($this->settings['bands'])->sortBy('max')->values();

        foreach ($bands as $band) {
            if ($mean <= $band['max'] + 1e-9) {
                return $band['label'];
            }
        }

        return $bands->last()['label'];
    }

    /**
     * @param  list<array{code: string, group: string, value: int|null, ms?: int|null, check?: bool}>  $answers  theo thứ tự hiện
     * @return array<string, mixed>
     */
    public function score(array $answers): array
    {
        $s = $this->settings;
        $sum = $count = $blank = array_fill_keys(str_split(self::ORDER), 0);

        foreach ($answers as $answer) {
            if (! empty($answer['check'])) {
                continue;
            }
            if ($answer['value'] === null) {
                $blank[$answer['group']]++;
            } else {
                $sum[$answer['group']] += $answer['value'];
                $count[$answer['group']]++;
            }
        }

        $groups = [];
        foreach (str_split(self::ORDER) as $g) {
            $mean = $count[$g] ? $sum[$g] / $count[$g] : 0.0;
            $groups[$g] = [
                'code' => $g, 'mean' => round($mean, 4), 'answered' => $count[$g], 'blank' => $blank[$g],
                'enough' => $count[$g] >= self::MIN_VALID_PER_GROUP,
                'band' => $count[$g] ? $this->band($mean) : 'Chưa đủ dữ liệu',
            ];
        }

        $rank = array_values($groups);
        usort($rank, fn ($x, $y) => [$y['enough'], $y['mean'], -strpos(self::ORDER, $y['code'])] <=> [$x['enough'], $x['mean'], -strpos(self::ORDER, $x['code'])]);
        $order = array_column($rank, 'code');
        [$g1, $g2, $g3, $g4] = $order;
        $m = fn (string $g): float => $groups[$g]['mean'];

        $thirdDetail = $m($g3) > $s['third_letter_threshold'] && $groups[$g3]['answered'] >= $s['items_per_group'];

        $gap = $s['near_tie_gap'] + 1e-9;
        $alternates = [];
        if ($m($g3) - $m($g4) <= $gap) {
            $alternates[] = $g1.$g2.$g4;
        }
        if ($m($g2) - $m($g3) <= $gap) {
            $alternates[] = $g1.$g3.$g2;
        }
        if ($m($g1) - $m($g2) <= $gap) {
            $alternates[] = $g2.$g1.$g3;
        }

        $rel = fn (string $a, string $b): array => ['pair' => $a.$b, 'distance' => self::hexDistance($a, $b), 'relation' => self::relation(self::hexDistance($a, $b))];
        $relations = ['1-2' => $rel($g1, $g2), '1-3' => $rel($g1, $g3), '2-3' => $rel($g2, $g3)];

        $x = $y = 0.0;
        foreach (str_split(self::ORDER) as $i => $g) {
            $x += $m($g) * cos(deg2rad($i * 60));
            $y += $m($g) * sin(deg2rad($i * 60));
        }
        $means = array_map($m, str_split(self::ORDER));

        $aboveMidpoint = count(array_filter([$g1, $g2, $g3], fn (string $g) => $m($g) > $s['interest_midpoint']));
        $profileCase = [3 => 'A', 2 => 'B', 1 => 'C', 0 => 'D'][$aboveMidpoint];

        return [
            'schema' => 'holland-result/3',
            'groups' => $groups,
            'order' => $order,
            'code2' => $g1.$g2,
            'code3' => $g1.$g2.$g3,
            'third_detail' => $thirdDetail,
            'alternates' => $alternates,
            'relations' => $relations,
            'consistency' => [1 => 'Cao', 2 => 'Trung bình', 3 => 'Thấp'][$relations['1-2']['distance']],
            'profile' => [
                'case' => $profileCase,
                'needs_counseling' => $aboveMidpoint <= 1,
                'elevation' => round(array_sum($means) / 6, 4),
                'differentiation' => round(max($means) - min($means), 4),
                'axis_x' => round($x, 4), 'axis_y' => round($y, 4), 'strength' => round(hypot($x, $y), 4),
            ],
            'quality' => $this->quality($answers, $groups),
        ];
    }

    /**
     * Cờ chất lượng bài làm: chọn một mức, chuỗi giống nhau, làm quá nhanh, đoạn bấm vội (và câu bỏ trống nếu có).
     *
     * @param  list<array<string, mixed>>  $answers
     * @param  array<string, array<string, mixed>>  $groups
     * @return array{reliability: string, flags: list<array{code: string, level: string, detail: string}>}
     */
    private function quality(array $answers, array $groups): array
    {
        $s = $this->settings;
        $flags = [];
        $scored = array_values(array_filter($answers, fn ($a) => empty($a['check'])));

        $blankTotal = array_sum(array_column($groups, 'blank'));
        if ($blankTotal > self::BLANK_FLAG_TOTAL) {
            $flags[] = ['code' => 'nhieu_khong_biet', 'level' => 'nhe', 'detail' => "{$blankTotal} câu bỏ trống"];
        }
        $short = array_keys(array_filter($groups, fn ($g) => ! $g['enough']));
        if ($short) {
            $flags[] = ['code' => 'nhom_thieu_du_lieu', 'level' => count($short) >= 2 ? 'nang' : 'nhe', 'detail' => implode(', ', $short)];
        }

        $values = array_filter(array_column($scored, 'value'), fn ($v) => $v !== null);
        if ($values) {
            $share = max(array_count_values($values)) / count($scored);
            if ($share >= $s['one_option_share']) {
                $flags[] = ['code' => 'mot_muc_chiem', 'level' => 'nang', 'detail' => round($share * 100).'% cùng một mức'];
            }
        }

        $run = $longest = 0;
        $previous = 'x';
        foreach ($scored as $answer) {
            $run = ($answer['value'] !== null && $answer['value'] === $previous) ? $run + 1 : 1;
            $previous = $answer['value'];
            $longest = max($longest, $run);
        }
        if ($longest >= $s['longstring']) {
            $flags[] = ['code' => 'chuoi_giong_nhau', 'level' => 'nhe', 'detail' => "{$longest} câu liên tiếp cùng mức"];
        }

        $times = array_filter(array_map(fn ($a) => $a['ms'] ?? null, $answers), fn ($v) => $v !== null);
        if ($times && count($times) === count($answers)) {
            $average = array_sum(array_map(fn ($v) => min($v, 60000), $times)) / count($times) / 1000;
            if ($average < $s['fast_avg_seconds']) {
                $flags[] = ['code' => 'qua_nhanh', 'level' => 'nang', 'detail' => round($average, 1).' giây/câu'];
            }
            $run = $best = 0;
            foreach ($times as $ms) {
                $run = $ms < $s['rushed_seconds'] * 1000 ? $run + 1 : 0;
                $best = max($best, $run);
            }
            if ($best >= $s['rushed_run']) {
                $flags[] = ['code' => 'doan_bam_voi', 'level' => 'nhe', 'detail' => "{$best} câu liên tiếp trả lời rất nhanh"];
            }
        }

        $heavy = count(array_filter($flags, fn ($f) => $f['level'] === 'nang'));
        $reliability = $heavy ? 'Chưa đủ tin cậy' : (count($flags) ? 'Cần xem lại' : 'Tin cậy');

        return ['reliability' => $reliability, 'flags' => $flags];
    }
}
