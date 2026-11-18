<?php

namespace App\Modules\Access\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Luật chung "có mã quyền X thì được xem, thêm, sửa, cho vào thùng rác, khôi phục" cho một loại dữ liệu.
 *           Lớp con chỉ cần khai báo mã quyền và chặn thêm các trường hợp riêng.
 * Ai gọi:   Laravel Gate (qua #[UsePolicy] trên model); Filament tự hỏi trước khi hiện menu, nút, trang.
 * Dữ liệu:  không đọc bảng nào ngoài quyền của tài khoản (User::hasPermission).
 */
abstract class PermissionPolicy
{
    /** Mã quyền trong bảng permissions, ví dụ questions.manage. */
    abstract protected function permission(): string;

    protected function allowed(User $user): bool
    {
        return $user->canAccessAdmin() && $user->hasPermission($this->permission());
    }

    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function view(User $user, Model $record): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user, Model $record): bool
    {
        return $this->allowed($user);
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->allowed($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function restore(User $user, Model $record): bool
    {
        return $this->allowed($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->allowed($user);
    }

    /**
     * Xóa hẳn: mặc định không cho ai làm. Dữ liệu cũ (lượt làm bài, lịch sử) có thể còn trỏ tới bản ghi.
     */
    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return $this->allowed($user);
    }

    public function replicate(User $user, Model $record): bool
    {
        return false;
    }
}
