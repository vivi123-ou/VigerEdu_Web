<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Nội dung trang Liên hệ: các key trong bảng settings (group "contact")
 * cùng giá trị mặc định (dùng khi chưa cấu hình trong admin).
 */
class ContactPage extends SiteContent
{
    public const DEFAULTS = [
        // Banner đầu trang
        'contact_hero_pretitle' => 'Liên hệ với chúng tôi',
        'contact_hero_title' => 'KẾT NỐI VỚI VIGER EDU',
        'contact_hero_description' => 'Đội ngũ VIGER EDU luôn sẵn sàng hỗ trợ và giải đáp mọi thắc mắc của bạn về lộ trình du học.',
        'contact_hero_image' => null, // null => ảnh mặc định bên dưới

        // Khối thông tin liên hệ
        'contact_heading' => 'Liên Hệ Ngay',
        'contact_heading_highlight' => 'VIGER EDU',
        'contact_phone' => '(084) 819949968',
        'contact_email' => 'info@spn.com.vn', // dùng chung với Footer
        'contact_address' => "6 Phùng Khắc Khoan,\nPhường Sài Gòn, TP.HCM",
        'contact_side_image' => null,

        // Mạng xã hội (dùng chung với Footer)
        'social_facebook' => 'https://www.facebook.com/duhocVIGER',
        'social_tiktok' => 'https://www.tiktok.com/@spn.invest',
        'social_youtube' => 'https://www.youtube.com/@SPN.Invest',

        // Form đăng ký
        'contact_form_pretitle' => 'Bắt đầu hành trình của riêng bạn',
        'contact_form_title' => "**Đức** đang chờ bạn.\nViger **EDU** cũng vậy.",
        'contact_form_description' => 'Để lại email — chúng tôi sẽ liên hệ tư vấn miễn phí trong 24 giờ.',
        'contact_form_button' => 'Đăng ký ngay',
        'contact_success_message' => 'Cảm ơn bạn! VIGER EDU sẽ liên hệ trong vòng 24 giờ.',

        // Bản đồ
        'contact_show_map' => '1',
        'contact_map_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.3500190135123!2d106.69857019999999!3d10.7844813!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f35e7dcdebb%3A0x9f036d91414b18fa!2zNiBQaMO5bmcgS2jhuq9jIEtob2FuLCBTw6BpIEfDsm4sIEjhu5MgQ2jDrSBNaW5oLCBWaeG7h3QgTmFt!5e0!3m2!1svi!2sus!4v1791193058666!5m2!1svi!2sus',
    ];

    public const DEFAULT_HERO_IMAGE = 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1600&q=80';

    public const DEFAULT_SIDE_IMAGE = 'img/contact man.png';

    /** Chấp nhận cả link embed lẫn nguyên đoạn <iframe ...> dán từ Google Maps; chỉ cho phép https. */
    public static function mapSrc(?string $value): ?string
    {
        $value = trim((string) $value);

        if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $value, $m)) {
            $value = html_entity_decode($m[1]);
        }

        return Str::startsWith($value, 'https://') ? $value : null;
    }
}
