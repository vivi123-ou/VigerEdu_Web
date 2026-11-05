<?php

/**
 * Holland (RIASEC) → đề xuất ngành – bản tham chiếu cho web Viger-Edu (PHP 7.4+).
 * Cùng công thức với file Excel Holland_de_xuat_nganh_v2.xlsx.
 *
 * Dùng:
 *   $cfg    = json_decode(file_get_contents('cau_hinh.json'), true);
 *   $nganh  = json_decode(file_get_contents('majors.json'), true);
 *   $answers = [['nhom'=>'R','tra_loi'=>1,'toi_da'=>1], ...];   // từ bảng câu trả lời
 *   $p   = holland_diem($answers);          // ['R'=>0.33, 'I'=>0.67, ...]
 *   $ma  = holland_ma($p);                  // ['ma'=>'AIS', 'di3'=>0.39, 'bang_diem'=>false, ...]
 *   $top = holland_de_xuat($p, $ma['ma'], $nganh, $cfg);
 */
const RIASEC = ['R', 'I', 'A', 'S', 'E', 'C'];

/** s(X,Y) = 3 − khoảng cách trên lục giác (trùng 3, kề 2, cách một 1, đối diện 0) */
function holland_s(string $x, string $y): int
{
    $d = abs(array_search($x, RIASEC) - array_search($y, RIASEC));

    return 3 - min($d, 6 - $d);
}

/** Bước 1: điểm % mỗi nhóm = tổng điểm / điểm tối đa */
function holland_diem(array $answers): array
{
    $sum = array_fill_keys(RIASEC, 0.0);
    $max = array_fill_keys(RIASEC, 0.0);
    foreach ($answers as $a) {
        $g = strtoupper($a['nhom']);
        if (! isset($sum[$g])) {
            continue;
        }
        $sum[$g] += (float) $a['tra_loi'];
        $max[$g] += (float) ($a['toi_da'] ?? 1);
    }
    $p = [];
    foreach (RIASEC as $g) {
        $p[$g] = $max[$g] > 0 ? $sum[$g] / $max[$g] : 0.0;
    }

    return $p;
}

/** Bước 2: mã 3 chữ (bằng điểm → theo thứ tự R-I-A-S-E-C), độ rõ DI3, độ nhất quán */
function holland_ma(array $p): array
{
    $order = RIASEC;
    usort($order, function ($a, $b) use ($p) {
        if ($p[$a] == $p[$b]) {
            return array_search($a, RIASEC) <=> array_search($b, RIASEC);
        }

        return $p[$b] <=> $p[$a];
    });
    $v = array_map(fn ($g) => $p[$g], $order);

    return [
        'ma' => $order[0].$order[1].$order[2],
        'di3' => $v[0] - ($v[1] + $v[3]) / 2,
        'nhat_quan' => holland_s($order[0], $order[1]),
        'bang_diem' => ($v[0] == $v[1] || $v[1] == $v[2] || $v[2] == $v[3]),
    ];
}

/** C-index: 3·s(X1,Y1) + 2·s(X2,Y2) + 1·s(X3,Y3)  (0–18) */
function holland_C(string $X, string $Y): int
{
    return 3 * holland_s($X[0], $Y[0]) + 2 * holland_s($X[1], $Y[1]) + holland_s($X[2], $Y[2]);
}

/** r = tương quan giữa 6 điểm % của HS và mã ngành (Y1=3, Y2=2, Y3=1, còn lại 0)
 *  Rút gọn: r = (3p(Y1)+2p(Y2)+p(Y3) − Σp) / (√8 · √Σ(p−p̄)²) */
function holland_r(array $p, string $Y): float
{
    $mean = array_sum($p) / 6;
    $dev = 0.0;
    foreach ($p as $v) {
        $dev += ($v - $mean) ** 2;
    }
    if ($dev == 0) {
        return 0.0;
    }
    $num = 3 * $p[$Y[0]] + 2 * $p[$Y[1]] + $p[$Y[2]] - array_sum($p);

    return $num / (sqrt(8) * sqrt($dev));
}

function holland_nhan(float $fit, array $cfg): string
{
    $n = $cfg['nguong'];
    if ($fit >= $n['rat_phu_hop']) {
        return 'Rất phù hợp';
    }
    if ($fit >= $n['phu_hop']) {
        return 'Phù hợp';
    }
    if ($fit >= $n['co_the_can_nhac']) {
        return 'Có thể cân nhắc';
    }

    return 'Ít phù hợp';
}

/** Bước 3: FIT của 1 ngành = max qua các mã của ngành */
function holland_fit(array $p, string $X, array $codes, array $cfg): array
{
    $best = null;
    foreach ($codes as $Y) {
        $Y = strtoupper(trim($Y));
        if (strlen($Y) !== 3) {
            continue;
        }
        $C = holland_C($X, $Y);
        $r = holland_r($p, $Y);
        $fit = $cfg['trong_so_khop_ma'] * $C / 18 + $cfg['trong_so_khop_diem'] * ($r + 1) / 2;
        if ($best === null || $fit > $best['fit']) {
            $best = ['fit' => $fit, 'ma_nganh' => $Y, 'khop_ma' => $C / 18, 'khop_diem' => ($r + 1) / 2, 'C' => $C, 'r' => $r];
        }
    }

    return $best;
}

/** Bước 4: xếp hạng ngành 'Đang tư vấn = Có', tách Đại học / Học nghề, kèm điểm lĩnh vực */
function holland_de_xuat(array $p, string $X, array $nganh, array $cfg): array
{
    $kq = ['Đại học' => [], 'Học nghề' => [], 'linh_vuc' => []];
    foreach ($nganh as $n) {
        if (empty($n['is_active'])) {
            continue;
        }
        $f = holland_fit($p, $X, $n['codes'], $cfg);
        if (! $f) {
            continue;
        }
        $row = $f + ['id' => $n['id'], 'ten' => $n['ten'], 'linh_vuc' => $n['linh_vuc'], 'nhan' => holland_nhan($f['fit'], $cfg)];
        $kq[$n['loai']][] = $row;
        $lv = $n['linh_vuc'];
        if (! isset($kq['linh_vuc'][$lv]) || $f['fit'] > $kq['linh_vuc'][$lv]['fit']) {
            $kq['linh_vuc'][$lv] = ['fit' => $f['fit'], 'nganh_tot_nhat' => $n['ten']];
        }
    }
    $cmp = fn ($a, $b) => $b['fit'] <=> $a['fit'];
    usort($kq['Đại học'], $cmp);
    usort($kq['Học nghề'], $cmp);
    uasort($kq['linh_vuc'], $cmp);
    $kq['Đại học'] = array_slice($kq['Đại học'], 0, $cfg['so_nganh_dai_hoc']);
    $kq['Học nghề'] = array_slice($kq['Học nghề'], 0, $cfg['so_nghe']);

    return $kq;
}

// ---- Chạy thử: php holland_engine.php ----
if (PHP_SAPI === 'cli' && realpath($argv[0]) === __FILE__) {
    $c = json_decode(file_get_contents(__DIR__.'/holland_config.json'), true);
    $cfg = ['trong_so_khop_ma' => $c['weight_code_match'], 'trong_so_khop_diem' => $c['weight_score_match'],
        'nguong' => ['rat_phu_hop' => 0.8, 'phu_hop' => 0.65, 'co_the_can_nhac' => 0.5],
        'so_nganh_dai_hoc' => $c['top_university'], 'so_nghe' => $c['top_vocational']];
    $nganh = json_decode(file_get_contents(__DIR__.'/majors.json'), true);
    $mau = ['R' => 3, 'I' => 6, 'A' => 8, 'S' => 5, 'E' => 1, 'C' => 2];   // khách mẫu KH0001, 9 câu/nhóm
    $answers = [];
    foreach ($mau as $g => $k) {
        for ($i = 0; $i < 9; $i++) {
            $answers[] = ['nhom' => $g, 'tra_loi' => $i < $k ? 1 : 0, 'toi_da' => 1];
        }
    }
    $p = holland_diem($answers);
    $ma = holland_ma($p);
    echo "Mã: {$ma['ma']}  DI3=".round($ma['di3'], 2)."\n";
    $kq = holland_de_xuat($p, $ma['ma'], $nganh, $cfg);
    foreach (['Đại học', 'Học nghề'] as $loai) {
        echo "== $loai\n";
        foreach ($kq[$loai] as $i => $n) {
            printf("%d. %-35s %s  khớp mã %3.0f%%  khớp điểm %3.0f%%  FIT %.2f  %s\n", $i + 1, $n['ten'], $n['ma_nganh'],
                $n['khop_ma'] * 100, $n['khop_diem'] * 100, $n['fit'], $n['nhan']);
        }
    }
}
