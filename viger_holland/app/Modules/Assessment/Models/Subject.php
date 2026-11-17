<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Môn học dùng cho chỉ số nền tảng học tập (điểm môn do CRM thu sau).
 * Ai gọi:   MajorGroup.subject_weights; CatalogSeeder.
 * Dữ liệu:  bảng subjects.
 */
#[Fillable(['code', 'name', 'is_active', 'sort_order'])]
class Subject extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
