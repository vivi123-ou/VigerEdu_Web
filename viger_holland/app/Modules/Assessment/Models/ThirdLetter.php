<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Hướng chuyên sâu khi một nhóm đứng thứ ba trong mã (dùng để ghép 120 mã) và đoạn "Chữ thứ ba" khách đọc
 *           trong báo cáo khi mã có 3 chữ.
 * Ai gọi:   CustomerReport (báo cáo khách); trang Danh mục (H6).
 * Dữ liệu:  bảng third_letters.
 */
#[Fillable(['code', 'specialty_vi', 'specialty_de', 'specialty_en', 'skills', 'description'])]
class ThirdLetter extends Model {}
