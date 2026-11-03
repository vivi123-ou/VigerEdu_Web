<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Danh sách chuyên mục mặc định
        $categories = [
            'uncategorized' => 'Uncategorized',
            'tong-quan' => 'Tổng quan',
            'he-thong-giao-duc' => 'Hệ thống giáo dục',
            'nghien-cuu-va-ung-dung' => 'Nghiên cứu và ứng dụng',
            'chi-phi-va-co-hoi' => 'Chi phí và cơ hội',
            'co-hoi-nghe-nghiep' => 'Cơ hội nghề nghiệp',
            'du-hoc-dai-hoc' => 'Du học Đại học',
            'du-hoc-cao-hoc' => 'Du học Cao học',
            'chuong-trinh-hoc' => 'Chương trình học',
            'hieu-ve-duc' => 'Hiểu về Đức',
            'cuoc-song-o-duc' => 'Cuộc sống ở Đức',
        ];

        foreach ($categories as $slug => $name) {
            Category::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
