<?php

namespace App\Support;

/**
 * Nội dung trang Về chúng tôi: các key trong bảng settings (group "about")
 * cùng giá trị mặc định (dùng khi chưa cấu hình trong admin).
 */
class AboutPage extends SiteContent
{
    public const DEFAULTS = [
        // Banner đầu trang
        'about_hero_pretitle' => 'Câu chuyện của chúng tôi',
        'about_hero_title' => 'VỀ CHÚNG TÔI',
        'about_hero_description' => 'Đối Tác Tin Cậy Trên Hành Trình Giáo Dục Quốc Tế Cùng VIGER EDU',
        'about_hero_image' => null, // null => ảnh mặc định bên dưới

        // Khối giới thiệu lộ trình
        'about_intro_label' => 'KHÁM PHÁ LỘ TRÌNH',
        'about_intro_heading' => 'Khám Phá Lộ Trình Bước Vào Giáo Dục Bậc Cao',
        'about_intro_highlight' => '**VIGER EDU** là đơn vị tư vấn du học chuyên sâu, tập trung vào các lộ trình giáo dục bậc cao tại Châu Âu, đặc biệt là CHLB Đức.',
        'about_intro_description' => 'Tại VIGER EDU, chúng tôi đã đồng hành và giúp đỡ vô số học viên hiện thực hóa ước mơ của mình bằng cách cung cấp các lộ trình giáo dục cá nhân hóa, sự hướng dẫn chuyên sâu và quy trình nộp hồ sơ đại học thành công, mang lại tỷ lệ đỗ cao vào các trường quốc tế.',
        'about_intro_image' => null,

        // Khối giá trị (accordion)
        'about_values_heading' => "NÂNG BƯỚC HỌC VIÊN\nBẰNG SỰ ĐỒNG HÀNH\nCHUYÊN BIỆT",
        'about_values_description' => 'Chúng tôi thiết kế dịch vụ phù hợp với nhu cầu riêng biệt của từng học viên, hỗ trợ các bạn nhập học thành công tại các học viện uy tín khắp Châu Âu và xây dựng sự nghiệp bền vững.',
        'about_values_items' => [
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

        // Khối hành trình phát triển
        'about_history_label' => 'HÀNH TRÌNH PHÁT TRIỂN',
        'about_history_heading' => 'Di Sản Kiến Tạo Tương Lai Qua Đường Giáo Dục',
        'about_history_description' => "Kể từ khi thành lập, VIGER EDU luôn cống hiến hết mình để định hướng cho học viên Việt Nam đạt được những thành tựu học thuật xuất sắc và các cơ hội nghề nghiệp quốc tế giá trị.\n\nVới nhiều năm kinh nghiệm, chúng tôi đã khẳng định uy tín vững chắc trong việc đồng hành cùng học viên suốt chặng đường, mang lại vô số kết quả nhập học thành công tại Đức và Châu Âu.",
        'about_history_image' => null,

        // Kêu gọi hành động cuối trang
        'about_cta_pretitle' => '✨ Bắt đầu hành trình của riêng bạn',
        'about_cta_title' => 'Khởi đầu hành trình chinh phục tương lai',
        'about_cta_description' => 'Liên hệ với chúng tôi ngay hôm nay để được tư vấn lộ trình học tập và sự nghiệp vững chắc tại nước ngoài.',
        'about_cta_button_text' => 'Bắt đầu ngay',
        'about_cta_button_url' => '/lien-he',
    ];

    public const JSON_KEYS = ['about_values_items'];

    public const DEFAULT_HERO_IMAGE = 'https://viger.edu.vn/wp-content/uploads/2026/04/pexels-photo-7972313.jpeg';

    public const DEFAULT_INTRO_IMAGE = 'https://viger.edu.vn/wp-content/uploads/2026/04/pexels-photo-34475611.jpeg';

    public const DEFAULT_HISTORY_IMAGE = 'https://viger.edu.vn/wp-content/uploads/2026/08/173095770936.webp';

    /** Tách văn bản thành các đoạn (ngăn cách bởi dòng trống). */
    public static function paragraphs(?string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R\s*\R/u', (string) $text)),
            'filled',
        ));
    }
}
