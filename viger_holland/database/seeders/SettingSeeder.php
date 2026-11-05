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
            'test' => [
                // Trang làm bài
                'test_title' => 'Trắc nghiệm Holland',
                'test_instruction' => 'Đọc từng câu và chọn mức đúng với bạn nhất. Hãy trả lời theo con người thật của bạn, không phải điều bạn nghĩ mình "nên" là.',
                'test_scale_title' => 'Cách chọn',
                'test_next' => 'Tiếp tục',
                'test_prev' => 'Quay lại',
                'test_unanswered' => 'Bạn còn câu chưa trả lời ở trang này.',
                'test_saved_note' => 'Bài làm được lưu tạm trên máy này, lỡ tải lại trang cũng không mất.',
                // Form "Nhận kết quả"
                'info_title' => 'Bạn đã hoàn thành!',
                'info_intro' => 'Chỉ một bước nữa thôi. Cho chúng tôi biết đôi chút về bạn để xem kết quả và nhận gợi ý ngành học phù hợp.',
                'info_privacy' => 'Thông tin của bạn chỉ dùng để gửi kết quả và tư vấn du học, không chia sẻ cho bên thứ ba.',
                'info_submit' => 'Xem kết quả',
                'info_error_summary' => 'Vui lòng kiểm tra lại các ô được đánh dấu.',
            ],
            'result' => [
                'result_title' => 'Kết quả trắc nghiệm của bạn',
                'result_greeting' => 'Chào',
                'result_intro' => 'Dưới đây là bức tranh về sở thích nghề nghiệp của bạn theo phương pháp Holland. Hãy xem đây là gợi ý để hiểu mình hơn, không phải lời khẳng định cuối cùng.',
                'result_code_label' => 'Mã Holland của bạn',
                'result_chart_title' => 'Mức độ sở thích theo 6 nhóm',
                'result_flat_note' => 'Các nhóm sở thích của bạn khá đồng đều, chưa có nhóm thật sự nổi trội. Điều này rất bình thường; trò chuyện với chuyên viên tư vấn sẽ giúp bạn làm rõ hơn.',
                'result_tie_note' => 'Một vài nhóm của bạn có mức điểm bằng nhau, nên thứ tự các chữ trong mã có thể đổi chỗ cho nhau.',
                'result_fields_title' => 'Lĩnh vực hợp với bạn',
                'result_university_title' => 'Ngành đại học gợi ý',
                'result_vocational_title' => 'Nghề gợi ý (học nghề)',
                'result_contact_title' => 'Nhận lộ trình tư vấn chi tiết',
                'result_contact_text' => 'Chuyên viên tư vấn sẽ liên hệ với bạn qua email hoặc điện thoại để gửi gợi ý trường, chương trình và lộ trình phù hợp với bạn. Muốn hỏi ngay? Gọi hoặc nhắn Zalo cho chúng tôi.',
                'result_call_text' => 'Gọi ngay',
                'result_zalo_text' => 'Nhắn Zalo',
                'result_disclaimer' => 'Kết quả mang tính tham khảo, giúp bạn định hướng. Quyết định chọn ngành nên dựa thêm vào năng lực, hoàn cảnh và tư vấn của người có chuyên môn.',
                'result_retake' => 'Làm lại bài trắc nghiệm',
            ],
            'email' => [
                'email_subject' => 'Kết quả trắc nghiệm Holland của bạn',
                'email_greeting' => 'Chào',
                'email_intro' => 'Cảm ơn bạn đã hoàn thành bài trắc nghiệm Holland. Đây là mã sở thích nghề nghiệp của bạn:',
                'email_button_text' => 'Xem đầy đủ kết quả',
                'email_closing' => 'Chuyên viên tư vấn sẽ liên hệ với bạn trong thời gian sớm nhất để gửi gợi ý trường, chương trình và lộ trình phù hợp.',
                'email_signature' => "Trân trọng,\nĐội ngũ tư vấn Viger-Edu",
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
