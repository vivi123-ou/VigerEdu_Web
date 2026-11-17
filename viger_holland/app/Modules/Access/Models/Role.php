<?php

namespace App\Modules\Access\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Làm gì:   Một vai trò tài khoản quản trị (Quản trị viên, Tư vấn viên, Nhân viên, Marketing…).
 * Ai gọi:   User::hasPermission(), trang Tài khoản & phân quyền (H6), RoleSeeder.
 * Dữ liệu:  bảng roles, role_permission. Không có dữ liệu khách.
 */
#[Fillable(['code', 'name', 'description', 'is_system', 'is_active', 'sort_order'])]
class Role extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_system' => 'boolean', 'is_active' => 'boolean'];
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }
}
