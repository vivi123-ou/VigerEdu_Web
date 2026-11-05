<?php

namespace App\Engines;

use App\Contracts\ScoringEngine;
use App\Data\HollandResult;
use App\Data\Major;
use App\Data\MajorMatch;
use App\Data\ScoringConfig;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Thuật toán bản chốt RIASEC FIT v2.
 * Port nguyên công thức từ database/seeders/data/holland/holland_engine_reference.php
 * (giải thích chi tiết: docs/Holland_de_xuat_nganh_v2.1.docx). KHÔNG đổi công thức.
 *
 *  1. p[g] = tổng điểm nhóm g / tổng điểm tối đa nhóm g
 *  2. Mã = 3 nhóm p cao nhất, bằng nhau xếp theo R I A S E C
 *  3. di3 = p1 − (p2 + p4)/2 · consistency = s(X1, X2) · has_tie khi p1=p2 | p2=p3 | p3=p4
 *  4. s(x, y) = 3 − min(|i − j|, 6 − |i − j|)
 *  5. code_match = (3·s(X1,Y1) + 2·s(X2,Y2) + s(X3,Y3)) / 18
 *  6. r = (3·p[Y1] + 2·p[Y2] + p[Y3] − Σp) / (√8 · √Σ(p − p̄)²), score_match = (r + 1)/2
 *  7. FIT = w1·code_match + w2·score_match, ngành nhiều mã lấy mã cho FIT cao nhất
 */
final class RiasecFitEngineV2 implements ScoringEngine
{
    public const NAME = 'riasec_fit_v2';

    public const VERSION = '2.0';

    public const GROUPS = ['R', 'I', 'A', 'S', 'E', 'C'];

    /** Sai số khi so sánh FIT bằng nhau (tránh lệch số thực ở chữ số rất nhỏ). */
    private const EPSILON = 1e-9;

    public function __construct(private readonly ScoringConfig $config = new ScoringConfig) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function version(): string
    {
        return self::VERSION;
    }

    public function score(array $answers, Collection $majors): HollandResult
    {
        return $this->scorePercent(self::percentFromAnswers($answers), $majors);
    }

    public function scorePercent(array $percent, Collection $majors): HollandResult
    {
        $p = self::normalizePercent($percent);
        $profile = self::profile($p);

        $matches = [];
        $fields = [];
        $testas = [];

        foreach ($majors as $major) {
            if (! $major->isActive) {
                continue;
            }

            $match = $this->matchMajor($p, $profile['code'], $major);
            if ($match === null) {
                continue;
            }

            $matches[] = $match;

            // Điểm lĩnh vực = FIT cao nhất trong lĩnh vực (giữ ngành gặp trước nếu bằng nhau, như bản tham chiếu)
            if (! isset($fields[$major->field]) || $match->fit > $fields[$major->field]['score']) {
                $fields[$major->field] = ['field' => $major->field, 'score' => $match->fit, 'best_major_code' => $major->code];
            }

            // Điểm nhóm TestAS = FIT cao nhất, tính cả nhóm chính và nhóm phụ
            foreach ($major->testasGroups() as $group) {
                if (! isset($testas[$group]) || $match->fit > $testas[$group]['score']) {
                    $testas[$group] = ['group' => $group, 'score' => $match->fit];
                }
            }
        }

        usort($matches, self::compareMatches(...));

        $fields = array_values($fields);
        usort($fields, fn (array $a, array $b) => self::compareFloatDesc($a['score'], $b['score']));

        $testas = array_values($testas);
        usort($testas, fn (array $a, array $b) => self::compareFloatDesc($a['score'], $b['score']) ?: strcmp($a['group'], $b['group']));

        $university = array_values(array_filter($matches, fn (MajorMatch $match) => $match->type === Major::TYPE_UNIVERSITY));
        $vocational = array_values(array_filter($matches, fn (MajorMatch $match) => $match->type === Major::TYPE_VOCATIONAL));

        return new HollandResult(
            engine: self::NAME,
            engineVersion: self::VERSION,
            percent: $p,
            code: $profile['code'],
            di3: $profile['di3'],
            consistency: $profile['consistency'],
            hasTie: $profile['has_tie'],
            topUniversity: array_slice($university, 0, $this->config->topUniversity),
            topVocational: array_slice($vocational, 0, $this->config->topVocational),
            fields: $fields,
            testas: $testas,
            ranking: $matches,
        );
    }

    /**
     * Bước 1: điểm % mỗi nhóm = tổng điểm / tổng điểm tối đa. Chạy với mọi số câu, mọi thang trả lời.
     *
     * @param  list<array{group: string, value: int|float, max?: int|float}>  $answers
     * @return array<string, float>
     */
    public static function percentFromAnswers(array $answers): array
    {
        $sum = array_fill_keys(self::GROUPS, 0.0);
        $max = array_fill_keys(self::GROUPS, 0.0);

        foreach ($answers as $answer) {
            $group = strtoupper((string) ($answer['group'] ?? ''));
            if (! isset($sum[$group])) {
                continue;
            }
            $sum[$group] += (float) ($answer['value'] ?? 0);
            $max[$group] += (float) ($answer['max'] ?? 1);
        }

        $percent = [];
        foreach (self::GROUPS as $group) {
            $percent[$group] = $max[$group] > 0 ? $sum[$group] / $max[$group] : 0.0;
        }

        return $percent;
    }

    /**
     * Bước 2: mã 3 chữ, di3, consistency, has_tie.
     *
     * @param  array<string, float>  $p
     * @return array{code: string, di3: float, consistency: int, has_tie: bool}
     */
    public static function profile(array $p): array
    {
        $order = self::GROUPS;
        usort($order, function (string $a, string $b) use ($p): int {
            if ($p[$a] == $p[$b]) {
                return array_search($a, self::GROUPS, true) <=> array_search($b, self::GROUPS, true);
            }

            return $p[$b] <=> $p[$a];
        });

        $v = array_map(fn (string $group) => $p[$group], $order);

        return [
            'code' => $order[0].$order[1].$order[2],
            'di3' => $v[0] - ($v[1] + $v[3]) / 2,
            'consistency' => self::similarity($order[0], $order[1]),
            'has_tie' => $v[0] == $v[1] || $v[1] == $v[2] || $v[2] == $v[3],
        ];
    }

    /**
     * s(x, y) = 3 − khoảng cách trên lục giác RIASEC (trùng 3, kề 2, cách một 1, đối diện 0).
     */
    public static function similarity(string $x, string $y): int
    {
        $distance = abs(array_search($x, self::GROUPS, true) - array_search($y, self::GROUPS, true));

        return 3 - min($distance, 6 - $distance);
    }

    /**
     * Khớp mã: C = 3·s(X1,Y1) + 2·s(X2,Y2) + s(X3,Y3), trong khoảng 0–18.
     */
    public static function codeIndex(string $studentCode, string $majorCode): int
    {
        return 3 * self::similarity($studentCode[0], $majorCode[0])
            + 2 * self::similarity($studentCode[1], $majorCode[1])
            + self::similarity($studentCode[2], $majorCode[2]);
    }

    /**
     * Khớp điểm: tương quan giữa 6 điểm % và mã ngành (Y1 = 3, Y2 = 2, Y3 = 1, còn lại 0). Mẫu số 0 thì r = 0.
     *
     * @param  array<string, float>  $p
     */
    public static function correlation(array $p, string $majorCode): float
    {
        $mean = array_sum($p) / 6;
        $deviation = 0.0;
        foreach ($p as $value) {
            $deviation += ($value - $mean) ** 2;
        }

        if ($deviation == 0) {
            return 0.0;
        }

        $numerator = 3 * $p[$majorCode[0]] + 2 * $p[$majorCode[1]] + $p[$majorCode[2]] - array_sum($p);

        return $numerator / (sqrt(8) * sqrt($deviation));
    }

    /**
     * Bước 3: FIT của một ngành = FIT cao nhất qua các mã của ngành.
     *
     * @param  array<string, float>  $p
     */
    private function matchMajor(array $p, string $studentCode, Major $major): ?MajorMatch
    {
        $best = null;

        foreach ($major->hollandCodes as $majorCode) {
            $codeMatch = self::codeIndex($studentCode, $majorCode) / 18;
            $scoreMatch = (self::correlation($p, $majorCode) + 1) / 2;
            $fit = $this->config->weightCodeMatch * $codeMatch + $this->config->weightScoreMatch * $scoreMatch;

            if ($best === null || $fit > $best['fit']) {
                $best = ['code' => $majorCode, 'code_match' => $codeMatch, 'score_match' => $scoreMatch, 'fit' => $fit];
            }
        }

        if ($best === null) {
            return null;
        }

        return new MajorMatch(
            majorCode: $major->code,
            name: $major->nameVi,
            field: $major->field,
            type: $major->type,
            codeUsed: $best['code'],
            codeMatch: $best['code_match'],
            scoreMatch: $best['score_match'],
            fit: $best['fit'],
            label: $this->config->labelFor($best['fit']),
            reason: $this->reason($p, $studentCode, $best['code']),
        );
    }

    /**
     * Ví dụ: "AIS ↔ IAS · bạn đạt I 67%, A 89%, S 56%".
     *
     * @param  array<string, float>  $p
     */
    private function reason(array $p, string $studentCode, string $majorCode): string
    {
        $scores = array_map(
            fn (string $group) => $group.' '.(int) round($p[$group] * 100).'%',
            str_split($majorCode),
        );

        return $studentCode.' ↔ '.$majorCode.' · '.$this->config->reasonSubject.' đạt '.implode(', ', $scores);
    }

    /**
     * Sắp FIT giảm dần → khớp mã giảm dần → mã ngành tăng dần.
     */
    private static function compareMatches(MajorMatch $a, MajorMatch $b): int
    {
        return self::compareFloatDesc($a->fit, $b->fit)
            ?: self::compareFloatDesc($a->codeMatch, $b->codeMatch)
            ?: strcmp($a->majorCode, $b->majorCode);
    }

    private static function compareFloatDesc(float $a, float $b): int
    {
        if (abs($a - $b) < self::EPSILON) {
            return 0;
        }

        return $b <=> $a;
    }

    /**
     * @param  array<string, int|float>  $percent
     * @return array<string, float>
     */
    private static function normalizePercent(array $percent): array
    {
        $normalized = [];
        foreach (self::GROUPS as $group) {
            if (! array_key_exists($group, $percent) || ! is_numeric($percent[$group])) {
                throw new InvalidArgumentException("Thiếu điểm % của nhóm {$group}.");
            }
            $normalized[$group] = max(0.0, min(1.0, (float) $percent[$group]));
        }

        return $normalized;
    }
}
