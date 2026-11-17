<?php

namespace App\Modules\Assessment\Policies;

use App\Modules\Access\Policies\PermissionPolicy;

/**
 * Làm gì:   Ai được xem, sửa, duyệt, tạm ngưng, cho câu hỏi vào thùng rác: người có quyền questions.manage.
 *           Không ai xóa hẳn câu hỏi, vì lượt làm bài cũ còn lưu mã câu đã rút.
 * Ai gọi:   Laravel Gate, trang Thư viện câu hỏi (Filament).
 * Dữ liệu:  bảng questions.
 */
class QuestionPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'questions.manage';
    }
}
