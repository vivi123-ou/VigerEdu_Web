<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Support\DefaultBanners;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banners = DefaultBanners::forModel();

        foreach ($banners as $data) {
            // title là cột đa ngôn ngữ (JSON) nên không dùng làm khóa tìm kiếm
            Banner::updateOrCreate(['image' => $data['image']], $data);
        }
    }
}
