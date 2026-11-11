<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\IntroBlocks;
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
                // Font giống web chính viger.edu.vn; chọn lại được trong danh sách config('holland.fonts')
                'font_body' => 'Work Sans',
                'font_heading' => 'Playfair Display',
            ],
            'contact' => [
                'company_name' => 'Viger-Edu – Tư vấn du học Đức',
                'footer_description' => 'Tư vấn du học Đức cho học sinh, sinh viên Việt Nam: định hướng ngành học, chọn trường, chuẩn bị hồ sơ và tiếng Đức.',
                'hotline' => '',
                'zalo' => '',
                'email' => 'info@spn.com.vn',
                'address' => '',
                'working_hours' => '',
                'footer_links' => json_encode([
                    ['label' => 'Facebook', 'url' => 'https://www.facebook.com/duhocVIGER', 'new_tab' => true],
                    ['label' => 'YouTube', 'url' => 'https://www.youtube.com/@SPN.Invest', 'new_tab' => true],
                    ['label' => 'TikTok', 'url' => 'https://www.tiktok.com/@spn.invest', 'new_tab' => true],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'header_menu' => '[]',
                'copyright' => 'Viger-Edu. Bảo lưu mọi quyền.',
                'footer_contact_heading' => 'Liên hệ',
                'footer_social_heading' => 'Theo dõi',
            ],
            'intro' => [
                'intro_eyebrow' => 'Trắc nghiệm hướng nghiệp Holland',
                'intro_title' => 'Bạn phù hợp với ngành học nào?',
                'intro_subtitle' => 'Bài trắc nghiệm Holland giúp bạn biết mình hứng thú với kiểu công việc nào, từ đó chọn ra những ngành nên tìm hiểu kỹ trước khi quyết định chọn trường và du học.',
                'intro_benefit_1' => 'Miễn phí',
                'intro_benefit_2' => 'Không có câu trả lời đúng hay sai',
                'intro_benefit_3' => 'Gợi ý lĩnh vực và ngành học phù hợp',
                'intro_button_text' => 'Bắt đầu làm bài',
                'intro_button_note' => 'Không cần tạo tài khoản.',
                'banner_image' => '',
                'banner_image_mobile' => '',

                // Các khối nội dung (Cách làm bài, 6 nhóm Holland, Câu hỏi thường gặp…), sửa ở tab "Các khối nội dung"
                'intro_blocks' => json_encode(IntroBlocks::defaults(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

                // Tên + mô tả 6 nhóm (dùng ở trang giới thiệu, trang kết quả, email)
                'group_name_R' => 'Kỹ thuật',
                'group_desc_R' => 'Thích làm việc thực hành với máy móc, dụng cụ, công trình.',
                'group_name_I' => 'Nghiên cứu',
                'group_desc_I' => 'Thích tìm hiểu, phân tích và giải quyết vấn đề.',
                'group_name_A' => 'Nghệ thuật',
                'group_desc_A' => 'Thích sáng tạo, thể hiện ý tưởng và cảm xúc.',
                'group_name_S' => 'Xã hội',
                'group_desc_S' => 'Thích giúp đỡ, hướng dẫn và làm việc với con người.',
                'group_name_E' => 'Quản lý',
                'group_desc_E' => 'Thích dẫn dắt, thuyết phục người khác và kinh doanh.',
                'group_name_C' => 'Nghiệp vụ',
                'group_desc_C' => 'Thích công việc có quy trình rõ ràng, cẩn thận với số liệu.',
            ],
            'embed' => [
                // Khối "Trắc nghiệm Holland" trên web chính (/embed, /api/v1/public/intro)
                'embed_title' => 'Trắc nghiệm hướng nghiệp Holland',
                'embed_subtitle' => 'Biết mình hứng thú với kiểu công việc nào và nhận gợi ý ngành học phù hợp trước khi chọn trường du học Đức. Miễn phí.',
                'embed_image' => '',
                'embed_button_text' => 'Làm bài trắc nghiệm',
                'embed_allowed_domains' => 'https://viger.edu.vn',
            ],
            'seo' => [
                'seo_home_title' => 'Trắc nghiệm Holland miễn phí – Bạn hợp với ngành nào?',
                'seo_home_description' => 'Trắc nghiệm Holland (RIASEC) miễn phí: xác định nhóm sở thích nghề nghiệp và nhận gợi ý ngành học phù hợp để định hướng du học Đức.',
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
