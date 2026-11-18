<?php

namespace App\Modules\Schools\Models;

use App\Modules\Assessment\Models\Major;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Làm gì:   Một chương trình học của một trường, gắn với một ngành trong danh mục (trường ↔ ngành).
 *           Một trường có thể có cùng ngành ở nhiều bậc (Bachelor, Master) → mỗi bậc một dòng.
 * Ai gọi:   trang "Trường & vùng" (thẻ Ngành của trường), "Ngành học & nghề gợi ý" (Số trường), ProgramImport,
 *           báo cáo nội bộ (trường có ngành gợi ý), API danh mục.
 * Dữ liệu:  bảng school_programs.
 */
#[Fillable(['school_id', 'major_id', 'degree', 'program_name', 'language', 'study_form', 'url', 'is_verified', 'note', 'extra'])]
class SchoolProgram extends Model
{
    /** Ô nhập được từ Excel: mã => tên hiện ở bước ghép cột. */
    public const IMPORTABLE = [
        'school' => 'Trường (mã, tên hoặc tên viết tắt)', 'major' => 'Ngành (mã, tên Việt hoặc tên Đức)', 'degree' => 'Bậc (Bachelor, Master…)',
        'program_name' => 'Tên chương trình tại trường', 'language' => 'Ngôn ngữ dạy', 'study_form' => 'Hình thức học',
        'url' => 'Link chương trình', 'is_verified' => 'Đã kiểm tra (có / không)', 'note' => 'Ghi chú',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_verified' => 'boolean', 'extra' => 'array'];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<Major, $this>
     */
    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class)->withTrashed();
    }
}
