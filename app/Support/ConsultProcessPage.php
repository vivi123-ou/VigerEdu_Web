<?php

namespace App\Support;

/**
 * Nội dung trang Quy trình tư vấn: các key trong bảng settings (group "consult_process")
 * cùng giá trị mặc định (dùng khi chưa cấu hình trong admin).
 */
class ConsultProcessPage extends SiteContent
{
    public const DEFAULTS = [
        // Banner đầu trang
        'consult_process_hero_pretitle' => 'Đồng hành cùng bạn',
        'consult_process_hero_title' => 'QUY TRÌNH TƯ VẤN DU HỌC',
        'consult_process_hero_description' => 'Minh bạch, chuyên nghiệp và tận tâm. Tại VIGER EDU, chúng tôi thiết kế một lộ trình rõ ràng giúp bạn dễ dàng theo dõi từng bước trên hành trình du học Đức của mình.',
        'consult_process_hero_image' => null, // null => ảnh mặc định bên dưới

        // Các bước quy trình
        'consult_process_steps_pretitle' => '✨ Quy trình du học',
        'consult_process_steps_title' => '05 bước đến giảng đường nước Đức',
        'consult_process_steps' => [
            ['title' => 'Tư vấn miễn phí', 'description' => 'Đánh giá hồ sơ và định hướng lộ trình phù hợp'],
            ['title' => 'Luyện tiếng Đức', 'description' => 'Đạt trình độ ngôn ngữ theo yêu cầu của trường'],
            ['title' => 'Chuẩn bị hồ sơ', 'description' => 'Hoàn thiện đầy đủ giấy tờ và nộp đơn'],
            ['title' => 'Xin visa', 'description' => 'Hỗ trợ toàn bộ thủ tục visa sinh viên'],
            ['title' => 'Nhập học & ổn định', 'description' => 'Đồng hành từ ngày đầu đến Đức'],
        ],

        // Dịch vụ tư vấn
        'consult_process_services_pretitle' => '✨ Dịch vụ tư vấn',
        'consult_process_services_title' => 'Toàn diện cho du học sinh',
        'consult_process_services_description' => 'Cung cấp hệ sinh thái dịch vụ trọn gói từ những bước chuẩn bị đầu tiên tại Việt Nam cho tới khi bạn hoàn toàn hòa nhập tại Đức.',
        'consult_process_services' => [
            [
                'title' => 'Định hướng Ngành/Trường',
                'body' => 'Phân tích học bạ, đánh giá năng lực tài chính và định hướng lộ trình vào các trường Đại học hàng đầu.',
                'icon' => 'book',
            ],
            [
                'title' => 'Hoàn thiện Hồ sơ',
                'body' => 'Dịch thuật, công chứng, viết Motivation Letter, xin chứng nhận APS và hỗ trợ nộp qua Uni-Assist.',
                'icon' => 'document',
            ],
            [
                'title' => 'Thủ tục Visa & Tài chính',
                'body' => 'Hỗ trợ mở tài khoản phong tỏa (Sperrkonto), luyện phỏng vấn Visa trực tiếp với tỷ lệ đỗ cao.',
                'icon' => 'globe',
            ],
            [
                'title' => 'Hỗ trợ sau khi bay',
                'body' => 'Tìm kiếm nhà ở, đón tại sân bay, hướng dẫn đăng ký hộ khẩu (Anmeldung) và nhập học tại trường.',
                'icon' => 'users',
            ],
        ],

        // Kêu gọi hành động cuối trang
        'consult_process_cta_pretitle' => '✨ Bắt đầu hành trình của riêng bạn',
        'consult_process_cta_title' => 'Khởi đầu hành trình chinh phục tương lai',
        'consult_process_cta_description' => 'Liên hệ với chúng tôi ngay hôm nay để được tư vấn lộ trình học tập và sự nghiệp vững chắc tại nước ngoài.',
        'consult_process_cta_button_text' => 'Bắt đầu ngay',
        'consult_process_cta_button_url' => '/lien-he',
    ];

    public const JSON_KEYS = ['consult_process_steps', 'consult_process_services'];

    public const DEFAULT_HERO_IMAGE = 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1600&q=80';

    /** Biểu tượng cho thẻ dịch vụ: key => [nhãn, thuộc tính d của <path> (heroicons outline 24px)]. */
    public const ICONS = [
        'book' => ['Sách / Ngành học', 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
        'document' => ['Hồ sơ / Giấy tờ', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'globe' => ['Quả địa cầu / Visa', 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'users' => ['Nhóm người / Hỗ trợ', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
        'academic' => ['Mũ tốt nghiệp', 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222'],
        'chat' => ['Trò chuyện / Tư vấn', 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        'money' => ['Tài chính', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'home' => ['Nhà ở', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        'plane' => ['Máy bay / Bay', 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8'],
        'language' => ['Ngôn ngữ', 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129'],
        'check' => ['Dấu tích / Hoàn thành', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];

    /** Danh sách cho Select trong admin: key => nhãn. */
    public static function iconOptions(): array
    {
        return array_map(fn (array $icon) => $icon[0], self::ICONS);
    }

    /** Thuộc tính d của <path> theo key (key lạ => biểu tượng mặc định). */
    public static function iconPath(?string $key): string
    {
        return (self::ICONS[$key] ?? self::ICONS['check'])[1];
    }
}
