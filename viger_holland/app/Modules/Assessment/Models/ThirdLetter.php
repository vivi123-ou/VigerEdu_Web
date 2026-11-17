<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Hướng chuyên sâu khi một nhóm đứng thứ ba trong mã (dùng để ghép 120 mã).
 * Ai gọi:   CodeCatalog (H2) ghép mô tả mã 3 chữ; trang Danh mục (H6).
 * Dữ liệu:  bảng third_letters.
 */
#[Fillable(['code', 'specialty_de', 'specialty_en', 'skills'])]
class ThirdLetter extends Model {}
