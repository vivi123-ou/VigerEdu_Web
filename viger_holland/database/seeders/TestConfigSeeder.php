<?php

namespace Database\Seeders;

use App\Modules\Assessment\Models\TestConfigVersion;
use App\Modules\Assessment\Support\TestConfig;
use Illuminate\Database\Seeder;

/**
 * Làm gì:   Tạo phiên bản cài đặt bài test đầu tiên (giá trị mặc định) nếu chưa có phiên bản nào.
 * Ai gọi:   DatabaseSeeder. Đã có phiên bản thì không làm gì (không ghi đè cài đặt admin đã lưu).
 * Dữ liệu:  bảng test_config_versions.
 */
class TestConfigSeeder extends Seeder
{
    public function run(): void
    {
        if (TestConfigVersion::query()->exists()) {
            return;
        }

        TestConfigVersion::create(['settings' => TestConfig::DEFAULTS, 'note' => 'Cài đặt ban đầu theo tài liệu của Thầy']);
    }
}
