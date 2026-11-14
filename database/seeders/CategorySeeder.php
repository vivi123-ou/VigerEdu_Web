<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'uncategorized' => [
                'name' => ['vi' => 'Uncategorized', 'en' => 'Uncategorized'],
                'menu_group' => null,
                'is_in_menu' => false,
                'sort_order' => 99,
            ],
            'tong-quan' => [
                'name' => ['vi' => 'Tổng quan', 'en' => 'Overview'],
                'menu_group' => 'giao-duc-tai-duc',
                'is_in_menu' => true,
                'sort_order' => 1,
            ],
            'he-thong-giao-duc' => [
                'name' => ['vi' => 'Hệ thống giáo dục', 'en' => 'Education System'],
                'menu_group' => 'giao-duc-tai-duc',
                'is_in_menu' => true,
                'sort_order' => 2,
            ],
            'nghien-cuu-va-ung-dung' => [
                'name' => ['vi' => 'Nghiên cứu và Ứng dụng', 'en' => 'Research & Applied Sciences'],
                'menu_group' => 'giao-duc-tai-duc',
                'is_in_menu' => true,
                'sort_order' => 3,
            ],
            'chi-phi-va-co-hoi' => [
                'name' => ['vi' => 'Chi phí và Cơ hội', 'en' => 'Costs & Opportunities'],
                'menu_group' => 'giao-duc-tai-duc',
                'is_in_menu' => true,
                'sort_order' => 4,
            ],
            'co-hoi-nghe-nghiep' => [
                'name' => ['vi' => 'Cơ hội nghề nghiệp', 'en' => 'Career Opportunities'],
                'menu_group' => 'giao-duc-tai-duc',
                'is_in_menu' => true,
                'sort_order' => 5,
            ],
            'du-hoc-dai-hoc' => [
                'name' => ['vi' => 'Du học Đại học', 'en' => 'Bachelor Studies'],
                'menu_group' => 'tu-van',
                'is_in_menu' => true,
                'sort_order' => 1,
            ],
            'du-hoc-cao-hoc' => [
                'name' => ['vi' => 'Du học Cao học', 'en' => 'Master Studies'],
                'menu_group' => 'tu-van',
                'is_in_menu' => true,
                'sort_order' => 2,
            ],
            'chuong-trinh-hoc' => [
                'name' => ['vi' => 'Chương trình học', 'en' => 'Study Programs'],
                'menu_group' => 'main',
                'is_in_menu' => true,
                'sort_order' => 1,
            ],
            'hieu-ve-duc' => [
                'name' => ['vi' => 'Hiểu về Đức', 'en' => 'Understanding Germany'],
                'menu_group' => 'blog',
                'is_in_menu' => true,
                'sort_order' => 1,
            ],
            'cuoc-song-o-duc' => [
                'name' => ['vi' => 'Cuộc sống ở Đức', 'en' => 'Life in Germany'],
                'menu_group' => 'blog',
                'is_in_menu' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($categories as $slug => $data) {
            Category::updateOrCreate(['slug' => $slug], $data);
        }
    }
}
