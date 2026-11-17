<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Làm gì:   Môi trường làm việc (văn phòng thiết kế, phòng thí nghiệm…).
 * Ai gọi:   Báo cáo nội bộ; trang Ngành và nghề (H6).
 * Dữ liệu:  bảng environments, environment_occupation.
 */
#[Fillable(['code', 'name', 'activities', 'interest_hint'])]
class Environment extends Model
{
    /**
     * @return BelongsToMany<Occupation, $this>
     */
    public function occupations(): BelongsToMany
    {
        return $this->belongsToMany(Occupation::class);
    }
}
