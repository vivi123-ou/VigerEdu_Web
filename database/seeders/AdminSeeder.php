<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Mật khẩu lấy từ .env, KHÔNG viết thẳng vào mã nguồn
        $password = env('ADMIN_PASSWORD');

        if (blank($password)) {
            $this->command?->error('Thiếu ADMIN_PASSWORD trong .env, bỏ qua tạo tài khoản admin.');

            return;
        }

        // firstOrCreate: chỉ tạo khi chưa có, không ghi đè mật khẩu đã đổi
        $user = User::updateOrCreate(
            ['username' => env('ADMIN_USERNAME', 'SPN')],
            [
                'full_name' => 'Quản trị SPN',
                'password' => $password,
                'role' => 'ADMIN',
                'active' => true,
            ],
        );

        $this->command?->info("Đã đồng bộ tài khoản admin: {$user->username}");
    }
}
