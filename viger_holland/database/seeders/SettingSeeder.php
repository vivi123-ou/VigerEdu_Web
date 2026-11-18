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
                'brand_title' => 'VIGER.EDU.VN',
                'site_tagline' => 'From Vietnam to Europe',
                'test_name' => 'Trắc nghiệm Holland',
                'logo_path' => 'img/logo.png',
                'favicon_path' => 'img/favicon.png',
                'primary_color' => '#c1121f',
                'text_color' => '#12263f',
                'main_site_url' => 'https://viger.edu.vn',
                'main_site_link_text' => 'Về trang chủ',
                'crm_url' => '',
                // Font theo mẫu giao diện đã chốt (Inter + Playfair Display); chọn lại được trong danh sách config('holland.fonts')
                'font_body' => 'Inter',
                'font_heading' => 'Playfair Display',
            ],
            'contact' => [
                'company_name' => 'Viger-Edu – Tư vấn du học Đức',
                'footer_description' => 'Tư vấn du học Đức cho học sinh, sinh viên Việt Nam: định hướng ngành học, chọn trường, chuẩn bị hồ sơ và tiếng Đức.',
                'hotline' => '0819 949 968',
                'zalo' => '',
                'email' => 'info@spn.com.vn',
                'address' => '6 Phùng Khắc Khoan, Phường Sài Gòn, TP.HCM',
                'working_hours' => '',
                'footer_links' => json_encode([
                    ['label' => 'Facebook', 'url' => 'https://www.facebook.com/duhocVIGER', 'new_tab' => true],
                    ['label' => 'YouTube', 'url' => 'https://www.youtube.com/@SPN.Invest', 'new_tab' => true],
                    ['label' => 'TikTok', 'url' => 'https://www.tiktok.com/@spn.invest', 'new_tab' => true],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'header_menu' => '[]',
                'copyright' => 'VIGER.EDU.VN',
                'footer_contact_heading' => 'Liên hệ',
                'footer_social_heading' => 'Theo dõi',
            ],
            'intro' => [
                // Chữ theo mẫu giao diện đã chốt (docs/mau_giao_dien/trang-gioi-thieu.html)
                'intro_eyebrow' => 'Trắc nghiệm Holland · RIASEC',
                'intro_title' => 'Bạn hợp với hướng ngành nào khi',
                'intro_title_accent' => 'du học Đức?',
                'intro_subtitle' => '{so_cau} câu hỏi ngắn về những hoạt động bạn thích. Sau khoảng 8 phút, bạn sẽ:',
                'intro_benefit_1' => 'biết mình nổi bật ở nhóm nào trong 6 nhóm sở thích Holland;',
                'intro_benefit_2' => 'thấy những ngành học tại Đức liên quan để tìm hiểu trước;',
                'intro_benefit_3' => 'được chuyên viên hỗ trợ phân tích kết quả, miễn phí.',
                'intro_button_text' => 'Bắt đầu làm bài',
                'intro_button_note' => '',
                'intro_sample_label' => 'Báo cáo mẫu',
                'intro_sample_code' => 'IS',
                'intro_sample_text' => 'Bạn có xu hướng thích tìm hiểu, phân tích vấn đề và dùng hiểu biết đó để hỗ trợ, phát triển con người.',
                'intro_sample_majors_heading' => 'Ngành học tại Đức nên tìm hiểu',
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
            'result' => [
                // Chữ trang làm bài và kết quả sơ lược (mẫu luong-khach.html); sửa ở "Chữ trên báo cáo & email" (H4)
                'quiz_legend_title' => 'Chú thích',
                'quiz_missing_text' => 'Chưa chọn câu trả lời',
                'quiz_missing_alert' => 'Bạn còn câu chưa chọn. Chọn số sao cho các câu viền đỏ rồi bấm tiếp.',
                'quiz_prev_text' => 'Phần trước',
                'quiz_next_text' => 'Tiếp tục',
                'quiz_finish_text' => 'Xem kết quả',
                'result_kicker' => 'Kết quả Holland của bạn',
                'result_majors_heading' => 'Ngành học tại Đức nên tìm hiểu',
                'result_pair_label' => 'Gợi ý chính',
                'result_more_heading' => 'Báo cáo đầy đủ giúp bạn hiểu:',
                'result_more_1' => 'Hai nhóm sở thích kết hợp với nhau thế nào',
                'result_more_2' => 'Mức độ quan tâm của bạn ở cả 6 nhóm',
                'result_more_3' => 'Vì sao từng ngành được đề xuất',
                'result_more_4' => 'Nghề có thể làm sau khi học',
                'result_button_text' => 'Xem báo cáo đầy đủ',
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
