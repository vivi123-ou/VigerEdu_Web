<?php

namespace App\Modules\Schools\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Làm gì:   Một quốc gia có trường (hiện có Đức). Thêm nước khác là thêm dòng, không sửa code.
 * Ai gọi:   trang "Trường & vùng", SchoolImport, SchoolSeeder.
 * Dữ liệu:  bảng countries.
 */
#[Fillable(['code', 'name', 'sort_order'])]
class Country extends Model
{
    /**
     * @return HasMany<Region, $this>
     */
    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    /**
     * @return HasMany<School, $this>
     */
    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }
}
