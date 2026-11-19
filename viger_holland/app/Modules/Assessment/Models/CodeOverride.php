<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Nội dung riêng cho một mã 3 chữ, thay phần tự ghép từ nhóm + cặp + chữ thứ ba.
 * Ai gọi:   CodeCatalog (H2); trang 120 mã (H6).
 * Dữ liệu:  bảng code_overrides.
 */
#[Fillable(['code', 'interpretation', 'exploration', 'note'])]
class CodeOverride extends Model {}
