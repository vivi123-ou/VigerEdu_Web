<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Về chúng tôi',
                'slug' => 've-chung-toi',
                'subtitle' => 'Đối Tác Tin Cậy Trên Hành Trình Giáo Dục Quốc Tế Cùng VIGER EDU',
                'banner_image' => 'https://viger.edu.vn/wp-content/uploads/2026/04/pexels-photo-7972313.jpeg',
                'content' => '<p>Sứ mệnh của VIGER EDU là tiếp sức cho các bạn trẻ thông qua những quyết định sáng suốt về lộ trình học tập và sự nghiệp, từ đó kiến tạo một tương lai bền vững trong môi trường toàn cầu.</p>',
                'sections' => [
                    [
                        'title' => 'Cam Kết Của Chúng Tôi Đối Với Sự Thành Công Của Bạn',
                        'body' => 'Sứ mệnh của VIGER EDU là tiếp sức cho các bạn trẻ thông qua những quyết định sáng suốt về lộ trình học tập và sự nghiệp, từ đó kiến tạo một tương lai bền vững trong môi trường toàn cầu.',
                    ],
                    [
                        'title' => 'Tầm Nhìn Về Một Tương Lai Với Nhiều Cơ Hội Toàn Cầu',
                        'body' => 'Chúng tôi hướng tới mục tiêu trở thành đơn vị tư vấn du học hàng đầu tại Việt Nam, là cầu nối đưa học viên đến với các trường đại học quốc tế danh tiếng, đồng thời thúc đẩy sự phát triển cá nhân và thành công trong sự nghiệp.',
                    ],
                    [
                        'title' => 'Triết Lý Dẫn Đường Trong Công Việc Của Chúng Tôi',
                        'body' => 'Các giá trị cốt lõi của VIGER EDU bao gồm sự chính trực, dịch vụ cá nhân hóa, cam kết hướng tới sự xuất sắc và niềm đam mê đồng hành cùng học viên đạt được các mục tiêu học tập cũng như sự nghiệp.',
                    ],
                    [
                        'title' => 'Ghi Nhận Cho Sự Xuất Sắc Của Chúng Tôi',
                        'body' => 'VIGER EDU đã nhận được nhiều lời tán dương và đánh giá cao cho những đóng góp nổi bật đối với sự thành công của học viên cũng như trong lĩnh vực tư vấn giáo dục quốc tế tại Việt Nam.',
                    ],
                ],
                'meta_description' => 'Về chúng tôi - VIGER EDU: Đồng hành cùng người trẻ Việt Nam chinh phục giáo dục Đức.',
                'is_published' => true,
            ],
            [
                'title' => 'Giáo dục tại Đức',
                'slug' => 'giao-duc-tai-duc',
                'subtitle' => 'Khám phá nền giáo dục hàng đầu Châu Âu với chi phí tối ưu',
                'banner_image' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1920&q=80',
                'content' => '<p>Nước Đức nổi tiếng với nền giáo dục học thuật chuẩn mực, các trường đại học hàng đầu thế giới và chính sách miễn học phí cho sinh viên quốc tế.</p>',
                'sections' => [],
                'meta_description' => 'Giới thiệu về hệ thống giáo dục đại học và sau đại học tại CHLB Đức.',
                'is_published' => true,
            ],
            [
                'title' => 'Tư vấn du học Đức',
                'slug' => 'tu-van',
                'subtitle' => 'Lộ trình tối ưu và đồng hành trọn gói từ chuyên gia',
                'banner_image' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1920&q=80',
                'content' => '<p>Dịch vụ tư vấn chuyên nghiệp từ định hướng chọn ngành, hoàn thiện hồ sơ APS, mở tài khoản phong tỏa đến xin visa và hòa nhập cuộc sống tại Đức.</p>',
                'sections' => [],
                'meta_description' => 'Dịch vụ tư vấn du học Đức uy tín, chuyên nghiệp từ VIGER EDU.',
                'is_published' => true,
            ],
            [
                'title' => 'Quy trình tư vấn',
                'slug' => 'tu-van/quy-trinh-tu-van',
                'subtitle' => 'Các bước đồng hành rõ ràng, minh bạch từ bước đầu tiên đến ngày nhập học',
                'banner_image' => 'https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=1920&q=80',
                'content' => '<p>Quy trình tư vấn của VIGER EDU gồm các bước: Đánh giá hồ sơ, chọn trường & ngành, chuẩn bị hồ sơ visa, tài chính và chuẩn bị hành trang bay sang Đức.</p>',
                'sections' => [],
                'meta_description' => 'Quy trình tư vấn du học Đức từng bước chi tiết tại VIGER EDU.',
                'is_published' => true,
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page);
        }
    }
}
