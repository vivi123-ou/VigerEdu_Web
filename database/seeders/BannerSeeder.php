<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banners = [
            [
                'image' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1920&q=80',
                'alt' => 'Khuôn viên đại học tại Đức',
                'eyebrow' => 'Du học Đức',
                'title' => 'Miễn học phí – Chất lượng quốc tế',
                'desc' => 'Khám phá hệ thống giáo dục Đức: học tại các trường công lập uy tín với mức học phí bằng 0 dành cho sinh viên quốc tế.',
                'button_primary_label' => 'Khám phá giáo dục Đức',
                'button_primary_url' => '/giao-duc-tai-duc',
                'button_secondary_label' => 'Nhận tư vấn miễn phí',
                'button_secondary_url' => '/lien-he',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=1920&q=80',
                'alt' => 'Sinh viên trong khuôn viên trường',
                'eyebrow' => 'Lộ trình cá nhân hóa',
                'title' => 'Từ chọn ngành đến ngày nhập học tại Đức',
                'desc' => 'Đội ngũ chuyên gia từng học tập và làm việc tại Đức đồng hành cùng bạn trong từng bước của hành trình.',
                'button_primary_label' => 'Xem quy trình tư vấn',
                'button_primary_url' => '/tu-van/quy-trinh-tu-van',
                'button_secondary_label' => 'Về chúng tôi',
                'button_secondary_url' => '/ve-chung-toi',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1607237138185-eedd9c632b0b?auto=format&fit=crop&w=1920&q=80',
                'alt' => 'Thư viện trường đại học',
                'eyebrow' => 'Cơ hội nghề nghiệp',
                'title' => 'Học tập hôm nay, làm việc tại Châu Âu ngày mai',
                'desc' => 'Sinh viên quốc tế được ở lại đến 18 tháng sau tốt nghiệp để tìm việc tại nền kinh tế lớn nhất Châu Âu.',
                'button_primary_label' => 'Cơ hội nghề nghiệp',
                'button_primary_url' => '/giao-duc-tai-duc/co-hoi-nghe-nghiep',
                'button_secondary_label' => null,
                'button_secondary_url' => null,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1920&q=80',
                'alt' => 'Nhóm sinh viên cùng học tập',
                'eyebrow' => 'Đồng hành trọn gói',
                'title' => 'Hồ sơ, visa, tài chính, nhà ở – chúng tôi lo cùng bạn',
                'desc' => 'Hỗ trợ dịch thuật, APS, tài khoản phong tỏa, luyện phỏng vấn visa và đón tại sân bay Đức.',
                'button_primary_label' => 'Dịch vụ tư vấn',
                'button_primary_url' => '/tu-van',
                'button_secondary_label' => 'Đặt lịch tư vấn',
                'button_secondary_url' => '/lien-he',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1599940824399-b87987ceb72a?auto=format&fit=crop&w=1920&q=80',
                'alt' => 'Sinh viên quốc tế tại Đức',
                'eyebrow' => 'Thành công đã được chứng minh',
                'title' => 'Hơn 500 học viên đã nhập học tại Đức',
                'desc' => 'Tỷ lệ xin visa thành công 95% cùng hơn 10 năm kinh nghiệm đồng hành với học sinh, sinh viên Việt Nam.',
                'button_primary_label' => 'Đọc kinh nghiệm từ blog',
                'button_primary_url' => '/blog',
                'button_secondary_label' => null,
                'button_secondary_url' => null,
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($banners as $data) {
            Banner::updateOrCreate(['title' => $data['title']], $data);
        }
    }
}
