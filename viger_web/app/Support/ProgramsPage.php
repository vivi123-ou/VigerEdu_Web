<?php

namespace App\Support;

/**
 * Trang Chương trình học (/chuong-trinh-hoc): banner đầu trang và khối kêu gọi cuối trang.
 * Các key trong bảng settings (group "programs") cùng giá trị mặc định.
 * Danh sách bài viết của trang được quản trị ở mục Bài viết (chuyên mục "chuong-trinh-hoc").
 */
class ProgramsPage extends CategoryHeroPages
{
    public const PREFIX = 'programs';

    public const PAGES = [
        'main' => [
            'label' => 'Chương trình học',
            'url' => '/chuong-trinh-hoc',
            'image' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1600&q=80',
        ],
    ];

    public const DEFAULTS = [
        // Banner đầu trang
        'programs_main_page_title' => 'Chương trình Đào tạo',
        'programs_main_pretitle' => 'Đa Dạng Lựa Chọn',
        'programs_main_heading' => 'CHƯƠNG TRÌNH ĐÀO TẠO',
        'programs_main_description' => 'Dù bạn vừa tốt nghiệp THPT, muốn nâng cao bằng cấp hay học nghề có lương, hệ thống giáo dục Đức luôn có một lộ trình hoàn hảo dành riêng cho bạn.',
        'programs_main_image' => null, // null => ảnh mặc định trong PAGES

        // Kêu gọi hành động cuối trang
        'programs_cta_pretitle' => '✨ Bắt đầu hành trình của riêng bạn',
        'programs_cta_title' => 'Chưa biết chọn chương trình nào?',
        'programs_cta_description' => 'Để lại thông tin, chuyên gia của VIGER EDU sẽ tư vấn lộ trình học tập miễn phí và phù hợp nhất với năng lực của bạn.',
        'programs_cta_button_text' => 'Nhận tư vấn ngay',
        'programs_cta_button_url' => '/lien-he',
    ];

    /** Bản tiếng Anh mặc định (admin sửa ở Chương trình học > Sửa bản English). */
    public const TRANSLATIONS = [
        'en' => [
            'programs_main_page_title' => 'Study Programs',
            'programs_main_pretitle' => 'A Wide Range of Options',
            'programs_main_heading' => 'STUDY PROGRAMS',
            'programs_main_description' => 'Whether you have just finished high school, want to earn a higher degree or prefer paid vocational training, the German education system has the perfect pathway for you.',

            'programs_cta_pretitle' => '✨ Start your own journey',
            'programs_cta_title' => 'Not sure which program to choose?',
            'programs_cta_description' => 'Leave your details and a VIGER EDU expert will advise you, free of charge, on the study pathway that best fits your abilities.',
            'programs_cta_button_text' => 'Get free advice',
        ],
    ];
}
