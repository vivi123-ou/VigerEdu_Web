<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'site_name',
                'value' => 'VIGER.EDU.VN',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Tên website / Thương hiệu',
            ],
            [
                'key' => 'site_slogan',
                'value' => 'From Vietnam to Europe',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Slogan phụ',
            ],
            [
                'key' => 'site_logo',
                'value' => 'img/logo.png',
                'group' => 'general',
                'type' => 'image',
                'label' => 'Đường dẫn logo',
            ],
            [
                'key' => 'header_cta_text',
                'value' => 'Bắt đầu ngay',
                'group' => 'header',
                'type' => 'text',
                'label' => 'Chữ trên nút CTA Header',
            ],
            [
                'key' => 'header_cta_url',
                'value' => '/lien-he',
                'group' => 'header',
                'type' => 'text',
                'label' => 'Liên kết nút CTA Header',
            ],
            [
                'key' => 'footer_description',
                'value' => 'Đồng hành cùng người trẻ Việt Nam trên hành trình chinh phục giáo dục Đức - miễn học phí, chất lượng quốc tế.',
                'group' => 'footer',
                'type' => 'textarea',
                'label' => 'Đoạn giới thiệu ở Footer',
            ],
            [
                'key' => 'contact_email',
                'value' => 'info@spn.com.vn',
                'group' => 'footer',
                'type' => 'text',
                'label' => 'Email liên hệ',
            ],
            [
                'key' => 'social_facebook',
                'value' => 'https://www.facebook.com/duhocVIGER',
                'group' => 'social',
                'type' => 'text',
                'label' => 'Link Facebook',
            ],
            [
                'key' => 'social_youtube',
                'value' => 'https://www.youtube.com/@SPN.Invest',
                'group' => 'social',
                'type' => 'text',
                'label' => 'Link YouTube',
            ],
            [
                'key' => 'social_tiktok',
                'value' => 'https://www.tiktok.com/@spn.invest',
                'group' => 'social',
                'type' => 'text',
                'label' => 'Link TikTok',
            ],
            [
                'key' => 'footer_copyright',
                'value' => 'Copyright © VIGER.EDU.VN. All rights reserved.',
                'group' => 'footer',
                'type' => 'text',
                'label' => 'Dòng bản quyền Footer',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
