<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Models\Major;
use App\Modules\Assessment\Models\RiasecPair;
use Illuminate\Support\Collection;

/**
 * Làm gì:   Gợi ý ngành từ mã 3 chữ, giữ nguyên thứ tự của bản tham chiếu: ngành nền của cặp 2 chữ đầu trước (xếp theo chỉ số C,
 *           bằng nhau theo điểm nhóm, rồi theo thứ tự Thầy đặt), sau đó các ngành khác có chữ đầu là nhóm khách "quan tâm"
 *           (trên "Mốc xét ngành ngoài cặp"), xếp theo chỉ số C, chỉ lấy ngành có nhãn mức khớp.
 *           Hai công tắc trong cài đặt: "Ưu tiên ngành nền của cặp mã" và "Chỉ gợi ý ngành đã kiểm tra nguồn chính thức".
 * Ai gọi:   AssessmentService::finish(); test 8 ca của Thầy.
 * Dữ liệu:  bảng majors (mã Holland, đang dùng, đã kiểm tra), riasec_pairs + pair_major (ngành nền, thứ tự); cài đặt TestConfig.
 */
final class MajorMatcher
{
    /**
     * @param  array<string, mixed>  $settings  TestConfig::current()
     */
    public function __construct(private readonly array $settings) {}

    /**
     * Toàn bộ danh sách cho tư vấn viên (mọi ngành đang dùng, kèm ngành chưa kiểm tra nguồn).
     *
     * @param  array<string, array{mean: float}>  $groups  điểm 6 nhóm từ ScoringEngine
     * @return list<array<string, mixed>>
     */
    public function recommend(string $code3, array $groups): array
    {
        $majors = Major::query()->where('is_active', true)->orderBy('sort_order')->get();
        $pair = RiasecPair::query()->where('pair', RiasecPair::keyFor($code3[0], $code3[1]))->with('majors')->first();
        $pairIds = $this->settings['pair_majors_first'] ? ($pair?->majors->pluck('id')->all() ?? []) : [];

        $pairRows = [];
        foreach ($pair?->majors ?? [] as $order => $major) {
            if (! in_array($major->id, $pairIds, true) || ! $major->is_active) {
                continue;
            }
            $best = $this->bestCode($major, $code3, $groups);
            $pairRows[] = $this->row($major, $best, 'pair', 'Ngành nền của cặp '.$pair->pair, $pair->pair) + ['orig' => $order];
        }
        usort($pairRows, fn ($x, $y) => [$y['c'] ?? -1, $y['w'], -$y['orig']] <=> [$x['c'] ?? -1, $x['w'], -$x['orig']]);

        $others = [];
        foreach ($majors as $major) {
            if (in_array($major->id, $pairIds, true) || empty($major->holland_codes)) {
                continue;
            }
            $best = $this->bestCode($major, $code3, $groups);
            if (($groups[$best['code'][0]]['mean'] ?? 0) <= $this->settings['interest_mark']) {
                continue;
            }
            $label = $this->label($best['c']);
            if ($label !== null) {
                $others[] = $this->row($major, $best, 'catalog', $label, null);
            }
        }
        usort($others, fn ($x, $y) => [$y['c'], $y['w'], $x['id']] <=> [$x['c'], $x['w'], $y['id']]);

        return collect([...$pairRows, ...$others])
            ->values()
            ->map(function (array $row, int $index): array {
                unset($row['orig']);

                return $row + ['rank' => $index + 1];
            })
            ->all();
    }

    /**
     * Danh sách khách thấy: tối đa "Gợi ý tối đa mấy ngành cho khách"; khi bật công tắc thì chỉ ngành đã kiểm tra nguồn chính thức.
     *
     * @param  list<array<string, mixed>>  $all
     * @return list<array<string, mixed>>
     */
    public function forCustomer(array $all): array
    {
        return collect($all)
            ->when($this->settings['only_verified_majors'], fn (Collection $rows) => $rows->where('verified', true))
            ->take((int) $this->settings['max_majors_public'])
            ->map(fn (array $row) => array_intersect_key($row, array_flip(['id', 'name_vi', 'name_de', 'label', 'routes'])))
            ->values()
            ->all();
    }

    /**
     * Mã Holland khớp nhất của ngành (ngành có thể có 1–3 mã): chỉ số C cao nhất, bằng nhau thì theo điểm các nhóm của mã.
     *
     * @param  array<string, array{mean: float}>  $groups
     * @return array{code: string|null, c: int|null, w: float}
     */
    private function bestCode(Major $major, string $code3, array $groups): array
    {
        $best = ['code' => null, 'c' => null, 'w' => 0.0];

        foreach ($major->holland_codes ?? [] as $code) {
            $c = ScoringEngine::cIndex($code3, $code);
            $w = 0.0;
            foreach ([3, 2, 1] as $i => $weight) {
                $w += isset($code[$i]) ? $weight * $groups[$code[$i]]['mean'] : 0;
            }
            if ($best['code'] === null || [$c, $w] > [$best['c'], $best['w']]) {
                $best = ['code' => $code, 'c' => $c, 'w' => round($w, 4)];
            }
        }

        return $best;
    }

    private function label(?int $c): ?string
    {
        if ($c === null) {
            return 'Chưa có mã ngành';
        }

        foreach (collect($this->settings['c_labels'])->sortByDesc('min') as $row) {
            if ($c >= $row['min']) {
                return $row['label'];
            }
        }

        return null;
    }

    /**
     * @param  array{code: string|null, c: int|null, w: float}  $best
     * @return array<string, mixed>
     */
    private function row(Major $major, array $best, string $source, string $label, ?string $pair): array
    {
        return [
            'id' => $major->code, 'name_vi' => $major->name_vi, 'name_de' => $major->name_de, 'routes' => $major->routes,
            'source' => $source, 'pair' => $pair, 'code' => $best['code'], 'c' => $best['c'], 'w' => $best['w'],
            'label' => $label, 'verified' => (bool) $major->is_verified,
        ];
    }
}
