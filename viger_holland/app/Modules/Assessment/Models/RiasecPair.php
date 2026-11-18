<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Làm gì:   Một trong 15 cặp Holland không thứ tự (RI, RA…), kèm ngành nền xếp theo thứ tự trong pair_major.
 * Ai gọi:   MajorMatcher (H2) lấy ngành nền của cặp; báo cáo khách; CatalogSeeder.
 * Dữ liệu:  bảng riasec_pairs, pair_major.
 */
#[Fillable(['pair', 'name', 'description', 'hex_distance', 'relation', 'occupations', 'foundation'])]
class RiasecPair extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occupations' => 'array',
            'hex_distance' => 'integer',
        ];
    }

    /**
     * Khóa cặp không thứ tự, viết theo thứ tự RIASEC: "IR" → "RI".
     */
    public static function keyFor(string $first, string $second): string
    {
        $letters = [$first, $second];
        usort($letters, fn (string $a, string $b) => array_search($a, RiasecType::ORDER, true) <=> array_search($b, RiasecType::ORDER, true));

        return implode('', $letters);
    }

    /**
     * @return BelongsToMany<Major, $this>
     */
    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class, 'pair_major')->withPivot('sort_order')->orderByPivot('sort_order');
    }
}
