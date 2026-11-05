<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Nội dung mẫu khi cài mới để không trang nào trống. Chỉ tạo khóa chưa có,
 * không ghi đè nội dung admin đã sửa.
 */
class SettingSeeder extends Seeder
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function defaults(): array
    {
        return [
            'general' => [
                'site_name' => 'Viger-Edu',
                'site_tagline' => 'From Vietnam to Europe',
                'test_name' => 'Trắc nghiệm Holland',
                'logo_path' => 'img/logo.png',
                'favicon_path' => 'img/favicon.png',
                'primary_color' => '#c1121f',
                'text_color' => '#12263f',
                'main_site_url' => 'https://viger.edu.vn',
                'main_site_link_text' => 'Về trang chủ',
                'crm_url' => '',
            ],
            'contact' => [
                'company_name' => 'Viger-Edu – Tư vấn du học Đức',
                'footer_description' => 'Đồng hành cùng người trẻ Việt Nam trên hành trình chinh phục giáo dục Đức – miễn học phí, chất lượng quốc tế.',
                'hotline' => '',
                'zalo' => '',
                'email' => 'info@spn.com.vn',
                'address' => '',
                'working_hours' => '',
                'facebook_url' => 'https://www.facebook.com/duhocVIGER',
                'youtube_url' => 'https://www.youtube.com/@SPN.Invest',
                'tiktok_url' => 'https://www.tiktok.com/@spn.invest',
                'copyright' => 'Viger-Edu. Bảo lưu mọi quyền.',
                'footer_contact_heading' => 'Liên hệ',
                'footer_social_heading' => 'Theo dõi',
            ],
            'intro' => [
                'intro_title' => 'Khám phá ngành học phù hợp với bạn',
                'intro_subtitle' => 'Bài trắc nghiệm Holland (RIASEC) miễn phí, khoảng 10–15 phút. Trả lời theo cảm nhận thật của bạn để nhận gợi ý lĩnh vực và ngành học phù hợp.',
                'intro_button_text' => 'Bắt đầu làm bài',
                'intro_fact_questions' => 'Số câu hỏi',
                'intro_fact_time_label' => 'Thời gian',
                'intro_fact_time' => '10–15 phút',
                'intro_fact_price_label' => 'Chi phí',
                'intro_fact_price' => 'Miễn phí',
                'intro_coming_soon' => 'Bài test đang được hoàn thiện, sẽ sớm mở cho mọi người.',
            ],
            'seo' => [
                'seo_home_title' => 'Trắc nghiệm Holland miễn phí – khám phá ngành học phù hợp',
                'seo_home_description' => 'Làm bài trắc nghiệm sở thích nghề nghiệp Holland (RIASEC) miễn phí trong 10–15 phút, nhận gợi ý lĩnh vực và ngành học phù hợp.',
            ],
            'integrations' => [
                'chatbot_embed_code' => '',
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::defaults() as $group => $values) {
            foreach ($values as $key => $value) {
                Setting::firstOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
            }
        }

        Setting::flushCache();
    }
}
