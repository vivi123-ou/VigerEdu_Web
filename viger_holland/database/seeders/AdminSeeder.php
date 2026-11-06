<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tạo tài khoản quản trị đầu tiên từ .env (HOLLAND_ADMIN_*).
 * Đã có tài khoản cùng tên đăng nhập thì giữ nguyên, không đổi mật khẩu.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = config('holland.admin');

        if (blank($admin['username']) || blank($admin['password'])) {
            $this->command?->warn('Bỏ qua tạo admin: chưa đặt HOLLAND_ADMIN_USERNAME / HOLLAND_ADMIN_PASSWORD trong .env');

            return;
        }

        $user = User::firstOrCreate(['username' => $admin['username']], [
            'name' => $admin['name'] ?: 'Quản trị viên',
            'email' => $admin['email'] ?: null,
            'password' => $admin['password'],
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->command?->info($user->wasRecentlyCreated
            ? "Đã tạo tài khoản quản trị: {$user->username}"
            : "Tài khoản quản trị {$user->username} đã có, giữ nguyên.");

        // Tài khoản marketing mẫu (không bắt buộc) để thử nghiệm thu. Trang quản lý tài khoản làm ở giai đoạn 6.
        $editor = config('holland.editor');
        if (filled($editor['username'] ?? null) && filled($editor['password'] ?? null)) {
            $created = User::firstOrCreate(['username' => $editor['username']], [
                'name' => $editor['name'] ?: 'Marketing',
                'password' => $editor['password'],
                'role' => User::ROLE_EDITOR,
                'is_active' => true,
            ]);
            $this->command?->info($created->wasRecentlyCreated ? "Đã tạo tài khoản marketing: {$created->username}" : "Tài khoản marketing {$created->username} đã có, giữ nguyên.");
        }
    }
}
