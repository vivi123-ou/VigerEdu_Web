<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Nạp dữ liệu ban đầu. Mọi seeder đều idempotent: chạy lại không trùng, không ghi đè nội dung đã sửa.
     * RoleSeeder chạy trước AdminSeeder vì tài khoản gắn với mã vai trò.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            RoleSeeder::class,
            AdminSeeder::class,
            CatalogSeeder::class,
            QuestionBankSeeder::class,
            TestConfigSeeder::class,
            FormFieldSeeder::class,
        ]);
    }
}
