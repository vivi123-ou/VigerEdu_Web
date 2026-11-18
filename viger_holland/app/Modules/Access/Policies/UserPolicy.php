<?php

namespace App\Modules\Access\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Ai được thêm, sửa, khóa tài khoản quản trị: người có quyền accounts.manage.
 *           Chặn thêm: chỉ quản trị viên mới sửa được tài khoản quản trị viên (không ai tự nâng quyền mình);
 *           không ai tự xóa tài khoản đang đăng nhập.
 * Ai gọi:   Laravel Gate, trang Tài khoản (Filament).
 * Dữ liệu:  bảng users.
 */
class UserPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'accounts.manage';
    }

    /**
     * @param  User  $record
     */
    public function update(User $user, Model $record): bool
    {
        return $this->allowed($user) && ($user->isAdmin() || ! $record->isAdmin());
    }

    /**
     * @param  User  $record
     */
    public function delete(User $user, Model $record): bool
    {
        return $this->update($user, $record) && ! $user->is($record);
    }

    /**
     * Tài khoản không xóa mềm; xóa nhiều cùng lúc dễ xóa nhầm nên không cho.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }
}
