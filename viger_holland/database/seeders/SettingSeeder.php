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
                'intro_eyebrow' => 'Trắc nghiệm hướng nghiệp Holland',
                'intro_title' => 'Bạn thật sự hợp với ngành nào?',
                'intro_subtitle' => 'Chưa biết nên học gì, đang phân vân giữa vài ngành, hay muốn chắc chắn trước khi đi du học? Bài trắc nghiệm Holland giúp bạn nhận ra mình thích làm việc theo cách nào – và những ngành học hợp với chính con người bạn.',
                'intro_benefit_1' => 'Hoàn toàn miễn phí',
                'intro_benefit_2' => 'Không có đúng – sai, chỉ cần trả lời thật lòng',
                'intro_benefit_3' => 'Gợi ý nhóm ngành và ngành học hợp với bạn',
                'intro_button_text' => 'Bắt đầu khám phá',
                'intro_button_note' => 'Không cần đăng ký để bắt đầu.',
                'intro_coming_soon' => 'Bài trắc nghiệm đang được hoàn thiện và sẽ sớm mở cho mọi người.',

                // Khối "Phương pháp Holland" + 6 nhóm sở thích
                'groups_title' => 'Phương pháp Holland là gì?',
                'groups_intro' => 'Do nhà tâm lý học người Mỹ John Holland xây dựng và được dùng rộng rãi trong tư vấn hướng nghiệp trên thế giới. Phương pháp chia sở thích nghề nghiệp thành 6 nhóm. Không ai chỉ thuộc một nhóm: mỗi người là sự kết hợp riêng, và ba nhóm nổi bật nhất tạo thành mã Holland của bạn – ví dụ "AIS". Người làm công việc hợp với mã của mình thường thấy hứng thú và gắn bó lâu dài hơn.',
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
                'step_1_title' => 'Trả lời theo cảm nhận',
                'step_1_text' => 'Chọn điều đúng với bạn nhất. Không có câu trả lời đúng hay sai, cũng không giới hạn thời gian.',
                'step_2_title' => 'Cho chúng tôi biết về bạn',
                'step_2_text' => 'Vài thông tin cơ bản để gửi kết quả và gợi ý đúng với hoàn cảnh của bạn.',
                'step_3_title' => 'Nhận kết quả và tư vấn',
                'step_3_text' => 'Xem ngay mã Holland và nhóm ngành hợp với bạn. Chuyên viên tư vấn sẽ gửi lộ trình chi tiết qua email.',
                'intro_parent_note' => 'Phụ huynh có thể làm cùng con, hoặc tự làm để hiểu con hơn.',
            ],
            'seo' => [
                'seo_home_title' => 'Trắc nghiệm Holland miễn phí – Bạn hợp với ngành nào?',
                'seo_home_description' => 'Khám phá nhóm sở thích nghề nghiệp của bạn theo phương pháp Holland (RIASEC) và nhận gợi ý ngành học phù hợp. Miễn phí, không cần đăng ký để bắt đầu.',
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
