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
                // Font giống web chính viger.edu.vn; chọn lại được trong danh sách config('holland.fonts')
                'font_body' => 'Work Sans',
                'font_heading' => 'Playfair Display',
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

                // Khối "Phương pháp Holland" + 6 nhóm sở thích
                'groups_title' => 'Phương pháp Holland',
                'groups_intro' => 'Do nhà tâm lý học John Holland xây dựng, phương pháp chia sở thích nghề nghiệp thành 6 nhóm. Mỗi người thường mạnh ở 2–3 nhóm; ba nhóm nổi bật nhất tạo thành mã Holland của bạn, ví dụ "AIS".',
                'group_name_R' => 'Kỹ thuật',
                'group_desc_R' => 'Thích làm việc thực hành, với máy móc, dụng cụ, công trình.',
                'group_name_I' => 'Nghiên cứu',
                'group_desc_I' => 'Thích tìm hiểu, phân tích, giải quyết vấn đề bằng tư duy.',
                'group_name_A' => 'Nghệ thuật',
                'group_desc_A' => 'Thích sáng tạo, tự do thể hiện ý tưởng và cảm xúc.',
                'group_name_S' => 'Xã hội',
                'group_desc_S' => 'Thích giúp đỡ, chia sẻ, làm việc cùng con người.',
                'group_name_E' => 'Quản lý',
                'group_desc_E' => 'Thích dẫn dắt, thuyết phục, kinh doanh.',
                'group_name_C' => 'Nghiệp vụ',
                'group_desc_C' => 'Thích làm việc có quy trình, cẩn thận với số liệu.',

                // Khối "Cách làm bài"
                'steps_title' => 'Cách làm bài',
                'step_1_title' => 'Điền thông tin',
                'step_1_text' => 'Vài thông tin cơ bản để chúng tôi gửi kết quả và tư vấn đúng nhu cầu.',
                'step_2_title' => 'Trả lời câu hỏi',
                'step_2_text' => 'Chọn theo cảm nhận thật của bạn, không có câu trả lời đúng hay sai.',
                'step_3_title' => 'Nhận kết quả',
                'step_3_text' => 'Xem mã Holland, lĩnh vực và ngành học gợi ý ngay sau khi nộp bài.',
                'intro_parent_note' => 'Phụ huynh có thể làm cùng con hoặc tự làm để hiểu thêm về con.',
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
