<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Làm gì:   Nhật ký hệ thống CRM — ghi các thao tác nhạy cảm (đăng nhập, phân quyền, nhân viên, xoá / xuất khách, cấu hình).
 * Ai gọi:   CrmAuditLog::record(...) trong các controller CRM; trang "Nhật ký hệ thống".
 * Dữ liệu:  bảng crm_audit_logs (chỉ thêm, không sửa). KHÔNG ghi tên / SĐT / email khách — chỉ mã KH.
 */
class CrmAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'description', 'ip'];

    /** action => [nhãn, màu badge] */
    public const ACTIONS = [
        'login' => ['Đăng nhập', 'gray'],
        'login_failed' => ['Đăng nhập sai', 'amber'],
        'roles.update' => ['Đổi phân quyền', 'violet'],
        'teams.change' => ['Nhóm làm việc', 'violet'],
        'staff.create' => ['Tạo nhân viên', 'blue'],
        'staff.update' => ['Sửa nhân viên', 'blue'],
        'staff.lock' => ['Khoá / mở khoá', 'amber'],
        'staff.reset' => ['Đặt lại mật khẩu', 'amber'],
        'leads.delete' => ['Xoá khách', 'red'],
        'leads.export' => ['Xuất dữ liệu', 'sky'],
        'notes.change' => ['Sửa / xoá ghi chú', 'gray'],
        'settings.update' => ['Đổi cấu hình', 'violet'],
    ];

    public static function record(string $action, string $description, ?int $userId = null): self
    {
        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'description' => mb_substr($description, 0, 1000),
            'ip' => request()?->ip(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::ACTIONS[$this->action][0] ?? $this->action;
    }

    public function color(): string
    {
        return self::ACTIONS[$this->action][1] ?? 'gray';
    }
}
