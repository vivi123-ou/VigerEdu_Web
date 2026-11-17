<?php

namespace App\Modules\Access\Policies;

use App\Models\User;
use App\Modules\Access\Models\Role;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Ai được thêm vai trò và tick quyền cho vai trò: người có quyền accounts.manage.
 *           Chặn thêm: vai trò Quản trị viên luôn có mọi quyền, không sửa được; vai trò lõi hoặc còn tài khoản thì không xóa được.
 * Ai gọi:   Laravel Gate, trang Vai trò & quyền (Filament).
 * Dữ liệu:  bảng roles, role_permission, users.
 */
class RolePolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'accounts.manage';
    }

    /**
     * @param  Role  $record
     */
    public function update(User $user, Model $record): bool
    {
        return $this->allowed($user) && $record->code !== User::ROLE_ADMIN;
    }

    /**
     * @param  Role  $record
     */
    public function delete(User $user, Model $record): bool
    {
        return $this->update($user, $record)
            && ! $record->is_system
            && ! User::query()->where('role', $record->code)->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
