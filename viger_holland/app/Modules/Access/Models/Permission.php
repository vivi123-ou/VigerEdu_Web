<?php

namespace App\Modules\Access\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Một quyền (ví dụ "Xem lượt làm bài", "Xuất dữ liệu"). Danh sách quyền gốc ở Access\Permissions.
 * Ai gọi:   User::hasPermission(), RoleSeeder, trang phân quyền (H6).
 * Dữ liệu:  bảng permissions.
 */
#[Fillable(['code', 'name', 'group', 'description', 'sort_order'])]
class Permission extends Model {}
