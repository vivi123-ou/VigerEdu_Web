<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Nạp dữ liệu ban đầu. Mọi seeder đều idempotent: chạy lại không trùng, không ghi đè nội dung đã sửa.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            QuestionSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
