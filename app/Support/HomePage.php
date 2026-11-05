<?php

namespace App\Support;

/**
 * Nội dung Trang chủ (welcome.blade.php): key trong bảng settings (group "home") + giá trị mặc định.
 */
class HomePage extends SiteContent
{
    public const JSON_KEYS = [
        'home_hero_stats',
        'home_partners',
        'home_process_steps',
        'home_testimonials',
    ];

    public const DEFAULTS = [
        // 1. Hero
        'home_hero_heading' => 'Hành trình của bạn',
        'home_hero_subheading' => 'Bắt đầu từ nước Đức...',
        'home_hero_description' => 'Viger EDU đồng hành cùng bạn từ lựa chọn ngành học, chuẩn bị hồ sơ đến khi đặt chân đến giảng đường Đức — với đội ngũ chuyên gia đã từng học tập và làm việc tại đây.',
        'home_hero_background' => null, // null => ảnh mặc định
        'home_hero_image' => null,
        'home_hero_stats' => [
            ['number' => 500, 'suffix' => '+', 'label' => 'Học viên nhập học'],
            ['number' => 95, 'suffix' => '%', 'label' => 'Xin Visa thành công'],
            ['number' => 10, 'suffix' => '+', 'label' => 'Năm kinh nghiệm'],
        ],
        'home_hero_button_text' => 'Đặt lịch tư vấn miễn phí',
        'home_hero_button_url' => '/lien-he',
        'home_roadmap_button_text' => 'Xem lộ trình du học',
        'home_roadmap_image' => null,

        // 2. Dải đối tác
        'home_partners' => [
            'Technical University of Munich (TUM)',
            'Ludwig-Maximilians-Universität München (LMU)',
            'Ruprecht-Karls-Universität Heidelberg',
            'Freie Universität Berlin',
        ],

        // 3. Về chúng tôi
        'home_about_label' => '✨ Về chúng tôi',
        'home_about_heading' => 'Người bạn đồng hành đáng tin cậy nhất trên con đường đến Đức',
        'home_about_description' => 'Chúng tôi không chỉ tư vấn — chúng tôi đồng hành. Đội ngũ Viger EDU gồm các chuyên gia tốt nghiệp từ các trường đại học Đức hàng đầu, hiểu rõ từng bước của hành trình từ ngày đầu đến ngày nhập học.',
        'home_about_link_text' => 'Tìm hiểu về chúng tôi',
        'home_about_link_url' => '/ve-chung-toi',
        'home_about_image_1' => null,
        'home_about_image_2' => null,
        'home_about_image_3' => null,
        'home_about_stat_number' => '300+',
        'home_about_stat_label' => 'Đối tác trường Đại học',

        // 4. Quy trình
        'home_process_pretitle' => '✨ Quy trình du học',
        'home_process_title' => '05 bước đến giảng đường nước Đức',
        'home_process_steps' => [
            ['title' => 'Tư vấn miễn phí', 'description' => 'Đánh giá hồ sơ và định hướng lộ trình phù hợp'],
            ['title' => 'Luyện tiếng Đức', 'description' => 'Đạt trình độ ngôn ngữ theo yêu cầu của trường'],
            ['title' => 'Chuẩn bị hồ sơ', 'description' => 'Hoàn thiện đầy đủ giấy tờ và nộp đơn'],
            ['title' => 'Xin visa', 'description' => 'Hỗ trợ toàn bộ thủ tục visa sinh viên'],
            ['title' => 'Nhập học & ổn định', 'description' => 'Đồng hành từ ngày đầu đến Đức'],
        ],
        'home_process_link_text' => 'Xem lộ trình chi tiết',
        'home_process_link_url' => '/tu-van/quy-trinh-tu-van',

        // 5. Cảm nhận học viên
        'home_testimonial_title' => 'Những hành trình đáng tự hào',
        'home_testimonials' => [
            [
                'content' => 'Nhờ có VIGER EDU, em đã tìm được ngôi trường đại học phù hợp nhất tại Đức cho ngành kỹ thuật của mình, sự hỗ trợ từ các anh chị thực sự tuyệt vời!',
                'name' => 'Duc Nguyen',
                'avatar' => null,
                'default_avatar' => 'img/duc_nguyen.jpg',
            ],
            [
                'content' => 'Em chưa từng nghĩ việc du học lại có thể dễ dàng đến thế! VIGER EDU đã giúp mọi thủ tục trở nên vô cùng nhanh chóng và nhẹ nhàng.',
                'name' => 'Mai Tran',
                'avatar' => null,
                'default_avatar' => 'img/mai_tran.jpg',
            ],
        ],

        // 6. CTA cuối trang
        'home_cta_pretitle' => '✨ Bắt đầu hành trình của riêng bạn',
        'home_cta_title' => 'Khởi đầu hành trình chinh phục tương lai',
        'home_cta_description' => 'Liên hệ với chúng tôi ngay hôm nay để được tư vấn lộ trình học tập và sự nghiệp vững chắc tại nước ngoài.',
        'home_cta_button_text' => 'Bắt đầu ngay',
        'home_cta_button_url' => '/lien-he',
    ];

    /** Ảnh mặc định cho các key ảnh (khi admin chưa upload). */
    public const DEFAULT_IMAGES = [
        'home_hero_background' => 'https://images.unsplash.com/photo-1618259278412-2819cbdea4dc?q=80&w=1121&auto=format&fit=crop',
        'home_hero_image' => 'img/trangchu.png',
        'home_roadmap_image' => 'img/lttv.jpg',
        'home_about_image_1' => 'https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=600&q=80',
        'home_about_image_2' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=600&auto=format&fit=crop',
        'home_about_image_3' => 'https://images.unsplash.com/photo-1607237138185-eedd9c632b0b?q=80&w=600&auto=format&fit=crop',
    ];

    /** URL ảnh của một key ảnh, có ảnh mặc định. */
    public static function image(string $key): ?string
    {
        return static::imageUrl(static::get($key), static::DEFAULT_IMAGES[$key] ?? null);
    }
}
