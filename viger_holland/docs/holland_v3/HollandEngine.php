<?php

/**
 * HollandEngine – bản tham chiếu thuật toán theo bản kiến trúc Career Map của Thầy.
 * PHP thuần, không phụ thuộc Laravel: khi đưa vào repo, chép logic vào
 * app/Services/Holland/* và giữ nguyên kết quả của tests/cases.json.
 *
 * Quy tắc:
 *  1. Điểm nhóm = trung bình các câu đã trả lời (1–5); "Không biết" (null) bỏ khỏi phép chia.
 *  2. Xếp hạng 6 nhóm, bằng điểm thì theo thứ tự R I A S E C.
 *  3. Chỉ số 2 chữ luôn có (có thứ tự). Chỉ số 3 chữ đọc chi tiết cho khách khi
 *     điểm nhóm thứ ba > ngưỡng (4,2) và nhóm đó đủ số câu (6).
 *  4. Quan hệ lục giác: 1 liền kề, 2 cách một, 3 đối kháng.
 *  5. Ngành: 2 ngành nền của cặp (xếp theo chỉ số C với mã có thứ tự),
 *     sau đó các ngành khác có chữ đầu là nhóm "Quan tâm", xếp theo chỉ số C (Brown & Gore 1994).
 */
final class HollandEngine
{
    public const ORDER = 'RIASEC';

    public function __construct(
        private array $settings,
        private array $pairs,      // riasec_pairs.json
        private array $third,      // third_letters.json
        private array $majors,     // majors.json
        private array $types,      // riasec_types.json
    ) {}

    public static function hexDistance(string $a, string $b): int
    {
        $d = abs(strpos(self::ORDER, $a) - strpos(self::ORDER, $b));
        return min($d, 6 - $d);
    }

    public static function relation(int $d): string
    {
        return [1 => 'Liền kề', 2 => 'Cách một', 3 => 'Đối kháng'][$d];
    }

    /** Chỉ số C của Brown & Gore: s = 3 − khoảng cách; trọng số 3,2,1 theo vị trí. */
    public static function cIndex(string $student, string $major): int
    {
        $c = 0;
        foreach ([3, 2, 1] as $i => $w) {
            if ($i >= strlen($major) || $i >= strlen($student)) {
                continue;
            }
            $c += $w * (3 - self::hexDistance($student[$i], $major[$i]));
        }
        return $c;
    }

    public function band(float $mean): string
    {
        foreach ($this->settings['bands'] as [$max, $label]) {
            if ($mean <= $max + 1e-9) {
                return $label;
            }
        }
        return end($this->settings['bands'])[1];
    }

    /**
     * @param array<int,array{code:string,group:string,value:?int,ms?:?int,check?:bool}> $answers theo thứ tự hiện
     */
    public function score(array $answers): array
    {
        $s = $this->settings;
        $sum = $cnt = $kb = array_fill_keys(str_split(self::ORDER), 0);
        foreach ($answers as $a) {
            if (!empty($a['check'])) {
                continue;
            }
            if ($a['value'] === null) {
                $kb[$a['group']]++;
            } else {
                $sum[$a['group']] += $a['value'];
                $cnt[$a['group']]++;
            }
        }
        $groups = [];
        foreach (str_split(self::ORDER) as $i => $g) {
            $mean = $cnt[$g] ? $sum[$g] / $cnt[$g] : 0.0;
            $groups[$g] = [
                'code' => $g, 'mean' => round($mean, 4), 'answered' => $cnt[$g], 'dont_know' => $kb[$g],
                'enough' => $cnt[$g] >= $s['min_valid_per_group'], 'band' => $cnt[$g] ? $this->band($mean) : 'Chưa đủ dữ liệu',
            ];
        }
        $rank = array_values($groups);
        usort($rank, fn ($x, $y) => [$y['enough'], $y['mean'], -strpos(self::ORDER, $y['code'])] <=> [$x['enough'], $x['mean'], -strpos(self::ORDER, $x['code'])]);
        $order = array_column($rank, 'code');
        [$g1, $g2, $g3, $g4] = $order;
        $m = fn ($g) => $groups[$g]['mean'];

        $code2 = $g1 . $g2;
        $code3 = $g1 . $g2 . $g3;
        $thirdDetail = $m($g3) > $s['third_letter_threshold']
            && (!$s['third_letter_requires_full'] || $groups[$g3]['answered'] >= $s['items_per_group']);

        $alternates = [];
        if ($m($g3) - $m($g4) <= $s['near_tie_gap'] + 1e-9) {
            $alternates[] = $g1 . $g2 . $g4;
        }
        if ($m($g2) - $m($g3) <= $s['near_tie_gap'] + 1e-9) {
            $alternates[] = $g1 . $g3 . $g2;
        }
        if ($m($g1) - $m($g2) <= $s['near_tie_gap'] + 1e-9) {
            $alternates[] = $g2 . $g1 . $g3;
        }

        $rel = fn ($a, $b) => ['pair' => $a . $b, 'distance' => self::hexDistance($a, $b), 'relation' => self::relation(self::hexDistance($a, $b))];
        $relations = ['1-2' => $rel($g1, $g2), '1-3' => $rel($g1, $g3), '2-3' => $rel($g2, $g3)];
        $consistency = [1 => 'Cao', 2 => 'Trung bình', 3 => 'Thấp'][$relations['1-2']['distance']];

        // Hai trục Prediger (mục 4.4 bản kiến trúc) – chỉ cho tư vấn viên
        $x = $y = 0.0;
        foreach (str_split(self::ORDER) as $i => $g) {
            $x += $m($g) * cos(deg2rad($i * 60));
            $y += $m($g) * sin(deg2rad($i * 60));
        }
        $means = array_map($m, str_split(self::ORDER));

        return [
            'schema' => 'holland-result/3',
            'groups' => $groups,
            'order' => $order,
            'code2' => $code2,
            'code3' => $code3,
            'third_detail' => $thirdDetail,
            'third' => $thirdDetail ? $this->thirdInfo($g3) : null,
            'alternates' => $alternates,
            'relations' => $relations,
            'consistency' => $consistency,
            'profile' => [
                'elevation' => round(array_sum($means) / 6, 4),
                'differentiation' => round(max($means) - min($means), 4),
                'axis_x' => round($x, 4), 'axis_y' => round($y, 4), 'strength' => round(hypot($x, $y), 4),
            ],
            'majors' => $this->recommend($code3, $groups),
            'flags' => $this->flags($answers, $groups),
        ];
    }

    private function thirdInfo(string $g): array
    {
        foreach ($this->third as $t) {
            if ($t['code'] === $g) {
                return $t;
            }
        }
        return [];
    }

    private function pairKey(string $a, string $b): string
    {
        return strpos(self::ORDER, $a) < strpos(self::ORDER, $b) ? $a . $b : $b . $a;
    }

    /** Ngành nền của cặp trước, sau đó ngành khác theo chỉ số C. */
    public function recommend(string $code3, array $groups): array
    {
        $s = $this->settings;
        $byId = array_column($this->majors, null, 'id');
        $pair = null;
        foreach ($this->pairs as $p) {
            if ($p['pair'] === $this->pairKey($code3[0], $code3[1])) {
                $pair = $p;
            }
        }
        $best = function (array $mj) use ($code3, $groups) {
            $b = null;
            foreach ($mj['codes'] as $c) {
                $ci = self::cIndex($code3, $c);
                $w = 0;
                foreach ([3, 2, 1] as $i => $wt) {
                    $w += isset($c[$i]) ? $wt * $groups[$c[$i]]['mean'] : 0;
                }
                if ($b === null || [$ci, $w] > [$b['c'], $b['w']]) {
                    $b = ['c' => $ci, 'w' => round($w, 4), 'code' => $c];
                }
            }
            return $b;
        };
        $label = function (?int $c) use ($s) {
            if ($c === null) {
                return 'Chưa có mã ngành';
            }
            foreach ($s['c_labels'] as [$min, $l]) {
                if ($c >= $min) {
                    return $l;
                }
            }
            return null;
        };
        $out = [];
        $pairRows = [];
        foreach ($pair['majors'] ?? [] as $k => $id) {
            $mj = $byId[$id] ?? null;
            if (!$mj || !$mj['active']) {
                continue; // ngành đã tắt trên admin thì không gợi ý
            }
            $b = $best($mj);
            $pairRows[] = ['id' => $id, 'name_vi' => $mj['name_vi'], 'name_de' => $mj['name_de'], 'source' => 'pair', 'pair' => $pair['pair'],
                'code' => $b['code'] ?? null, 'c' => $b['c'] ?? null, 'w' => $b['w'] ?? 0, 'orig' => $k, 'label' => 'Ngành nền của cặp ' . $pair['pair']];
        }
        usort($pairRows, fn ($x, $y) => [$y['c'] ?? -1, $y['w'], -$y['orig']] <=> [$x['c'] ?? -1, $x['w'], -$x['orig']]);
        $out = $pairRows;
        $others = [];
        foreach ($this->majors as $mj) {
            if (!$mj['active'] || in_array($mj['id'], $pair['majors'] ?? [], true) || !$mj['codes']) {
                continue;
            }
            $b = $best($mj);
            if ($groups[$b['code'][0]]['mean'] <= $s['interest_mark']) {
                continue; // chữ đầu của ngành là nhóm em chưa "Quan tâm"
            }
            $l = $label($b['c']);
            if ($l === null) {
                continue;
            }
            $others[] = ['id' => $mj['id'], 'name_vi' => $mj['name_vi'], 'name_de' => $mj['name_de'], 'source' => 'catalog', 'pair' => null,
                'code' => $b['code'], 'c' => $b['c'], 'w' => $b['w'], 'label' => $l];
        }
        usort($others, fn ($x, $y) => [$y['c'], $y['w'], $x['id']] <=> [$x['c'], $x['w'], $y['id']]);
        foreach ($others as $o) {
            $out[] = $o;
        }
        foreach ($out as $i => &$r) {
            $r['rank'] = $i + 1;
            unset($r['orig']);
        }
        return $out;
    }

    private function flags(array $answers, array $groups): array
    {
        $s = $this->settings;
        $f = [];
        $scored = array_values(array_filter($answers, fn ($a) => empty($a['check'])));
        $kbTotal = array_sum(array_column($groups, 'dont_know'));
        if ($kbTotal > $s['dont_know_flag_total']) {
            $f[] = ['code' => 'nhieu_khong_biet', 'level' => 'nhe', 'detail' => "$kbTotal câu \"Không biết\""];
        }
        $short = array_keys(array_filter($groups, fn ($g) => !$g['enough']));
        if ($short) {
            $f[] = ['code' => 'nhom_thieu_du_lieu', 'level' => count($short) >= 2 ? 'nang' : 'nhe', 'detail' => implode(', ', $short)];
        }
        $vals = array_filter(array_column($scored, 'value'), fn ($v) => $v !== null);
        if ($vals) {
            $share = max(array_count_values($vals)) / count($scored);
            if ($share >= $s['one_option_share']) {
                $f[] = ['code' => 'mot_muc_chiem', 'level' => 'nang', 'detail' => round($share * 100) . '% cùng một mức'];
            }
        }
        $run = $longest = 0;
        $prev = 'x';
        foreach ($scored as $a) {
            $run = ($a['value'] !== null && $a['value'] === $prev) ? $run + 1 : 1;
            $prev = $a['value'];
            $longest = max($longest, $run);
        }
        if ($longest >= $s['longstring']) {
            $f[] = ['code' => 'chuoi_giong_nhau', 'level' => 'nhe', 'detail' => "$longest câu liên tiếp cùng mức"];
        }
        $ms = array_filter(array_map(fn ($a) => $a['ms'] ?? null, $answers), fn ($v) => $v !== null);
        if (count($ms) === count($answers) && $ms) {
            $avg = array_sum(array_map(fn ($v) => min($v, 60000), $ms)) / count($ms) / 1000;
            if ($avg < $s['fast_avg_seconds']) {
                $f[] = ['code' => 'qua_nhanh', 'level' => 'nang', 'detail' => round($avg, 1) . ' giây/câu'];
            }
            $run = $best = 0;
            foreach ($ms as $v) {
                $run = $v < $s['rushed_seconds'] * 1000 ? $run + 1 : 0;
                $best = max($best, $run);
            }
            if ($best >= $s['rushed_run']) {
                $f[] = ['code' => 'doan_bam_voi', 'level' => 'nhe', 'detail' => "$best câu liên tiếp dưới 1 giây"];
            }
        }
        $heavy = count(array_filter($f, fn ($x) => $x['level'] === 'nang'));
        $light = count($f) - $heavy;
        $reliability = $heavy ? 'Chưa đủ tin cậy' : ($light ? 'Cần xem lại' : 'Tin cậy');
        return ['reliability' => $reliability, 'items' => $f];
    }
}
