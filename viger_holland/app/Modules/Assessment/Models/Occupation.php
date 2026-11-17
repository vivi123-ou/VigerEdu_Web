<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Làm gì:   Nghề nghiệp (Tầng 4): nhiệm vụ, kỹ năng, môi trường, yêu cầu bằng cấp.
 * Ai gọi:   Báo cáo khách và nội bộ (nghề tham khảo của ngành); trang Ngành và nghề (H6).
 * Dữ liệu:  bảng occupations, major_occupation, environment_occupation.
 */
#[Fillable(['code', 'name_vi', 'name_de', 'name_en', 'sector', 'tasks', 'skills', 'qualification', 'regulation', 'riasec_hypothesis', 'source', 'validation_status', 'is_active'])]
class Occupation extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Major, $this>
     */
    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class)->withPivot(['code', 'link_type', 'routes', 'source', 'validation_status'])->withTimestamps();
    }

    /**
     * @return BelongsToMany<Environment, $this>
     */
    public function environments(): BelongsToMany
    {
        return $this->belongsToMany(Environment::class);
    }
}
