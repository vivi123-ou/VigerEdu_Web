<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Làm gì:   Cụm ngành; subject_weights và gpa_weight dùng tính chỉ số nền tảng học tập (chỉ nội bộ xem).
 * Ai gọi:   MajorMatcher (H2); báo cáo nội bộ (H6); CatalogSeeder.
 * Dữ liệu:  bảng major_groups.
 */
#[Fillable(['name', 'subject_weights', 'gpa_weight', 'sort_order'])]
class MajorGroup extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject_weights' => 'array',
            'gpa_weight' => 'float',
        ];
    }

    /**
     * @return HasMany<Major, $this>
     */
    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }
}
