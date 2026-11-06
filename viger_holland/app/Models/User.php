<?php

namespace App\Models;

use App\Support\AdminMenu;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'ADMIN';

    /** Marketing: chỉ sửa nội dung trang, xem lượt làm bài. */
    public const ROLE_EDITOR = 'EDITOR';

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

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Tài khoản được vào trang quản trị: đang hoạt động và có vai trò hợp lệ.
     */
    public function canAccessAdmin(): bool
    {
        return $this->is_active && array_key_exists((string) $this->role, config('holland.roles'));
    }

    /**
     * Được vào một mục menu admin không. Quản trị viên: mọi mục. Marketing (EDITOR): mục có 'editor' => true
     * trong config/admin_menu.php (nội dung trang, thư viện ảnh, mô tả 6 nhóm, lượt làm bài).
     */
    public function canAccessMenu(string $key): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return (bool) (AdminMenu::find($key)['editor'] ?? false);
    }

    public function roleLabel(): string
    {
        return config('holland.roles.'.$this->role, (string) $this->role);
    }
}
