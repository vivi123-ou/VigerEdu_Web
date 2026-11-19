<?php

namespace App\Modules\Schools\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Làm gì:   Một vùng của quốc gia (ở Đức: bang), kèm thông tin giới thiệu cho tư vấn viên.
 * Ai gọi:   trang "Trường & vùng", SchoolImport, SchoolSeeder.
 * Dữ liệu:  bảng regions.
 */
#[Fillable(['country_id', 'code', 'name', 'name_alt', 'capital', 'founded_year', 'area_km2', 'population', 'density', 'hdi', 'gdp_per_capita', 'highlights', 'description', 'extra'])]
class Region extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['extra' => 'array'];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return HasMany<School, $this>
     */
    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }
}
