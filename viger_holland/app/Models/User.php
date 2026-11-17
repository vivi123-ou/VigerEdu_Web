<?php

namespace App\Models;

use App\Modules\Access\Models\Role;
use App\Support\AdminMenu;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Làm gì:   Tài khoản quản trị Holland. Quyền lấy theo vai trò (bảng roles / permissions), không viết cứng.
 * Ai gọi:   đăng nhập admin, middleware admin / menu, mọi controller kiểm tra quyền.
 * Dữ liệu:  bảng users (cột role = mã vai trò, ví dụ ADMIN), roles, role_permission.
 */
#[Fillable(['name', 'username', 'email', 'password', 'role', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Mã các vai trò có sẵn (Access\Permissions::DEFAULT_ROLES). Quyền thật nằm trong bảng, sửa được. */
    public const ROLE_ADMIN = 'ADMIN';

    public const ROLE_COUNSELOR = 'COUNSELOR';

    public const ROLE_STAFF = 'STAFF';

    public const ROLE_EDITOR = 'EDITOR';

    /** @var list<string>|null Quyền của tài khoản, nạp một lần mỗi request. */
    private ?array $permissionCodes = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'code');
    }

    /**
     * Quản trị viên luôn có mọi quyền (kể cả khi bảng quyền bị sửa nhầm), để không ai tự khóa mình khỏi hệ thống.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function hasPermission(string $code): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $this->permissionCodes ??= ($this->roleModel?->is_active ?? false)
            ? $this->roleModel->permissions()->pluck('code')->all()
            : [];

        return in_array($code, $this->permissionCodes, true);
    }

    /**
     * Tài khoản được vào trang quản trị: đang hoạt động và vai trò còn hiệu lực.
     */
    public function canAccessAdmin(): bool
    {
        return $this->is_active && ($this->isAdmin() || ($this->roleModel?->is_active ?? false));
    }

    /**
     * Xuất Excel, tải cơ sở dữ liệu, sao lưu (mặc định chỉ quản trị viên).
     */
    public function canExportData(): bool
    {
        return $this->hasPermission('data.export');
    }

    /**
     * Được vào một mục menu admin không: theo mã quyền ghi ở mục đó trong config/admin_menu.php.
     */
    public function canAccessMenu(string $key): bool
    {
        $item = AdminMenu::find($key);

        if ($item === null) {
            return false;
        }

        return isset($item['permission']) ? $this->hasPermission($item['permission']) : $this->isAdmin();
    }

    public function roleLabel(): string
    {
        return $this->roleModel?->name ?? (string) $this->role;
    }
}
