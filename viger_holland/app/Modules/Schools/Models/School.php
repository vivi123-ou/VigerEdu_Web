<?php

namespace App\Modules\Schools\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Làm gì:   Một trường đại học. Loại hình, hình thức là chữ (theo file nguồn của từng nước); cột khác lưu ở extra.
 *           Trường ẩn (is_hidden) không hiện trong danh sách tư vấn mặc định.
 * Ai gọi:   trang "Trường & vùng", SchoolImport, SchoolSeeder.
 * Dữ liệu:  bảng schools.
 */
#[Fillable(['country_id', 'region_id', 'external_code', 'short_name', 'name', 'display_name', 'type', 'ownership', 'students', 'founded_year', 'phd_rights', 'city', 'address', 'phone', 'website', 'is_hidden', 'extra'])]
class School extends Model
{
    /** Ô của trường nhập được từ Excel: mã => tên hiện ở bước ghép cột. */
    public const IMPORTABLE = [
        'external_code' => 'Mã trường', 'name' => 'Tên trường', 'short_name' => 'Tên viết tắt', 'display_name' => 'Tên thường dùng',
        'type' => 'Loại hình', 'ownership' => 'Hình thức', 'region' => 'Vùng / bang', 'students' => 'Số sinh viên',
        'founded_year' => 'Năm thành lập', 'phd_rights' => 'Quyền cấp tiến sĩ', 'city' => 'Thành phố', 'address' => 'Địa chỉ',
        'phone' => 'Điện thoại', 'website' => 'Trang web',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['extra' => 'array', 'is_hidden' => 'boolean', 'phd_rights' => 'boolean'];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Ngành trường này dạy (mỗi bậc một dòng).
     *
     * @return HasMany<SchoolProgram, $this>
     */
    public function programs(): HasMany
    {
        return $this->hasMany(SchoolProgram::class);
    }
}
