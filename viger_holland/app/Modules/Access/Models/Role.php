<?php

namespace App\Modules\Access\Models;

use App\Modules\Access\Policies\RolePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Làm gì:   Một vai trò tài khoản quản trị (Quản trị viên, Tư vấn viên, Nhân viên…).
 * Ai gọi:   User::hasPermission(), trang Vai trò & quyền (Filament), RoleSeeder.
 * Dữ liệu:  bảng roles, role_permission. Không có dữ liệu khách.
 */
#[Fillable(['code', 'name', 'description', 'is_system', 'is_active', 'sort_order'])]
#[UsePolicy(RolePolicy::class)]
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
