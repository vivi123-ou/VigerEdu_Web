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

        // Tài khoản nhân viên mẫu (không bắt buộc) để thử nghiệm thu. Thêm tài khoản khác ở trang Tài khoản.
        $staff = config('holland.staff');
        if (filled($staff['username'] ?? null) && filled($staff['password'] ?? null)) {
            $created = User::firstOrCreate(['username' => $staff['username']], [
                'name' => $staff['name'] ?: 'Nhân viên',
                'password' => $staff['password'],
                'role' => User::ROLE_STAFF,
                'is_active' => true,
            ]);
            $this->command?->info($created->wasRecentlyCreated ? "Đã tạo tài khoản nhân viên: {$created->username}" : "Tài khoản nhân viên {$created->username} đã có, giữ nguyên.");
        }
    }
}
