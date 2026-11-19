<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Một trong 6 nhóm Holland R, I, A, S, E, C; hex_index là vị trí trên lục giác (R=0 … C=5).
 * Ai gọi:   Bộ máy phân tích (H2) tính quan hệ lục giác; báo cáo khách; trang Danh mục (H6).
 * Dữ liệu:  bảng riasec_types.
 */
#[Fillable(['code', 'name', 'label', 'description', 'hex_index'])]
class RiasecType extends Model
{
    /** Thứ tự chuẩn RIASEC, cũng là thứ tự xếp khi bằng điểm. */
    public const ORDER = ['R', 'I', 'A', 'S', 'E', 'C'];
}
