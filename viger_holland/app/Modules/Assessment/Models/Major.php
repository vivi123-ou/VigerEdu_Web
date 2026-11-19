<?php

namespace App\Modules\Assessment\Models;

use App\Modules\Schools\Models\SchoolProgram;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Làm gì:   Ngành học tại Đức; holland_codes gồm 1–3 mã 3 chữ, nguồn ghi ở code_source.
 * Ai gọi:   MajorMatcher (H2) xếp ngành theo chỉ số C; báo cáo; trang Ngành và nghề (H6).
 * Dữ liệu:  bảng majors, pair_major, major_occupation, school_programs (trường có ngành).
 */
#[Fillable(['code', 'name_vi', 'name_de', 'name_en', 'major_group_id', 'holland_codes', 'code_source', 'routes', 'validation_status', 'source_url', 'is_verified', 'is_active', 'sort_order'])]
class Major extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'holland_codes' => 'array',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<MajorGroup, $this>
     */
    public function majorGroup(): BelongsTo
    {
        return $this->belongsTo(MajorGroup::class);
    }

    /**
     * @return BelongsToMany<Occupation, $this>
     */
    public function occupations(): BelongsToMany
    {
        return $this->belongsToMany(Occupation::class)->withPivot(['code', 'link_type', 'routes', 'source', 'validation_status'])->withTimestamps();
    }

    /**
     * Dòng liên kết ngành – nghề (sửa được trên admin, mã LNK… tự đặt).
     *
     * @return HasMany<MajorOccupationLink, $this>
     */
    public function occupationLinks(): HasMany
    {
        return $this->hasMany(MajorOccupationLink::class);
    }

    /**
     * Trường có ngành này (module Schools).
     *
     * @return HasMany<SchoolProgram, $this>
     */
    public function programs(): HasMany
    {
        return $this->hasMany(SchoolProgram::class);
    }

    /**
     * @return BelongsToMany<RiasecPair, $this>
     */
    public function pairs(): BelongsToMany
    {
        return $this->belongsToMany(RiasecPair::class, 'pair_major')->withPivot('sort_order');
    }
}
