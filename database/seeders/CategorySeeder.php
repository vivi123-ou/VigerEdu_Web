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
                'name' => 'Uncategorized',
                'menu_group' => null,
                'is_in_menu' => false,
                'sort_order' => 99,
            ],
            'tong-quan' => [
                'name' => 'Tổng quan',
                'menu_group' => 'giao-duc-tai-duc',
                'is_in_menu' => true,
                'sort_order' => 1,
            ],
            'he-thong-giao-duc' => [
                'name' => 'Hệ thống giáo dục',
                'menu_group' => 'giao-duc-tai-duc',
                'is_in_menu' => true,
                'sort_order' => 2,
            ],
            'nghien-cuu-va-ung-dung' => [
                'name' => 'Nghiên cứu và Ứng dụng',
                'menu_group' => 'giao-duc-tai-duc',
                'is_in_menu' => true,
                'sort_order' => 3,
            ],
            'chi-phi-va-co-hoi' => [
                'name' => 'Chi phí và Cơ hội',
                'menu_group' => 'giao-duc-tai-duc',
                'is_in_menu' => true,
                'sort_order' => 4,
            ],
            'co-hoi-nghe-nghiep' => [
                'name' => 'Cơ hội nghề nghiệp',
                'menu_group' => 'giao-duc-tai-duc',
                'is_in_menu' => true,
                'sort_order' => 5,
            ],
            'du-hoc-dai-hoc' => [
                'name' => 'Du học Đại học',
                'menu_group' => 'tu-van',
                'is_in_menu' => true,
                'sort_order' => 1,
            ],
            'du-hoc-cao-hoc' => [
                'name' => 'Du học Cao học',
                'menu_group' => 'tu-van',
                'is_in_menu' => true,
                'sort_order' => 2,
            ],
            'chuong-trinh-hoc' => [
                'name' => 'Chương trình học',
                'menu_group' => 'main',
                'is_in_menu' => true,
                'sort_order' => 1,
            ],
            'hieu-ve-duc' => [
                'name' => 'Hiểu về Đức',
                'menu_group' => 'blog',
                'is_in_menu' => true,
                'sort_order' => 1,
            ],
            'cuoc-song-o-duc' => [
                'name' => 'Cuộc sống ở Đức',
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
