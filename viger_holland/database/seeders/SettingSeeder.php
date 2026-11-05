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
                'footer_description' => 'Tư vấn du học Đức cho học sinh, sinh viên Việt Nam: định hướng ngành học, chọn trường, chuẩn bị hồ sơ và tiếng Đức.',
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
                'intro_title' => 'Bạn phù hợp với ngành học nào?',
                'intro_subtitle' => 'Bài trắc nghiệm Holland giúp bạn biết mình hứng thú với kiểu công việc nào, từ đó chọn ra những ngành nên tìm hiểu kỹ trước khi quyết định chọn trường và du học.',
                'intro_benefit_1' => 'Miễn phí',
                'intro_benefit_2' => 'Không có câu trả lời đúng hay sai',
                'intro_benefit_3' => 'Gợi ý lĩnh vực và ngành học phù hợp',
                'intro_button_text' => 'Bắt đầu làm bài',
                'intro_button_note' => 'Không cần tạo tài khoản.',

                // Khối "Phương pháp Holland" + 6 nhóm sở thích
                'groups_title' => 'Phương pháp Holland là gì?',
                'groups_intro' => 'Phương pháp do nhà tâm lý học John Holland (Mỹ) xây dựng, được dùng phổ biến trong hướng nghiệp. Sở thích nghề nghiệp được chia thành 6 nhóm. Mỗi người thường có điểm ở nhiều nhóm; ba nhóm cao nhất tạo thành mã Holland, ví dụ AIS. Người làm công việc phù hợp với mã Holland của mình thường hứng thú và gắn bó với nghề lâu hơn.',
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

                // Khối "Cách làm bài"
                'steps_title' => 'Cách làm bài',
                'step_1_title' => 'Trả lời các câu hỏi',
                'step_1_text' => 'Chọn mức đúng nhất với bạn. Không có câu trả lời đúng hay sai và không giới hạn thời gian.',
                'step_2_title' => 'Điền thông tin nhận kết quả',
                'step_2_text' => 'Một số thông tin cơ bản để gửi kết quả cho bạn và để chuyên viên tư vấn liên hệ khi cần.',
                'step_3_title' => 'Xem kết quả và nhận tư vấn',
                'step_3_text' => 'Xem ngay mã Holland và các ngành gợi ý. Chuyên viên tư vấn sẽ liên hệ để trao đổi thêm về ngành, trường và lộ trình du học.',
                'intro_parent_note' => 'Phụ huynh có thể làm cùng con hoặc tự làm để hiểu thêm về con.',
            ],
            'test' => [
                // Trang làm bài
                'test_title' => 'Trắc nghiệm Holland',
                'test_instruction' => 'Đọc từng câu và chọn mức đúng nhất với bạn. Hãy trả lời theo suy nghĩ và thói quen hiện tại của bạn, không theo điều bạn cho là nên làm.',
                'test_scale_title' => 'Cách chọn',
                'test_next' => 'Tiếp tục',
                'test_prev' => 'Quay lại',
                'test_unanswered' => 'Trang này còn câu chưa trả lời.',
                'test_saved_note' => 'Câu trả lời được lưu tạm trên thiết bị này, tải lại trang không bị mất.',
                // Form "Nhận kết quả"
                'info_title' => 'Bạn đã trả lời xong',
                'info_intro' => 'Vui lòng điền thông tin dưới đây để xem kết quả và nhận tư vấn chọn ngành.',
                'info_privacy' => 'Thông tin của bạn chỉ được dùng để gửi kết quả và tư vấn du học, không chia sẻ cho bên thứ ba.',
                'info_submit' => 'Xem kết quả',
                'info_error_summary' => 'Vui lòng kiểm tra lại các ô được đánh dấu.',
            ],
            'result' => [
                'result_title' => 'Kết quả trắc nghiệm của bạn',
                'result_greeting' => 'Chào',
                'result_intro' => 'Kết quả dưới đây cho biết mức độ hứng thú của bạn với 6 nhóm nghề nghiệp theo phương pháp Holland. Đây là thông tin tham khảo giúp bạn hiểu mình hơn khi chọn ngành.',
                'result_code_label' => 'Mã Holland của bạn',
                'result_chart_title' => 'Mức độ hứng thú theo 6 nhóm',
                'result_flat_note' => 'Điểm các nhóm của bạn khá đều nhau, chưa có nhóm nào nổi bật rõ. Trường hợp này khá phổ biến. Chuyên viên tư vấn có thể cùng bạn trao đổi thêm để tìm hướng phù hợp.',
                'result_tie_note' => 'Một số nhóm của bạn có điểm bằng nhau, vì vậy thứ tự các chữ trong mã Holland có thể thay đổi.',
                'result_fields_title' => 'Lĩnh vực phù hợp',
                'result_university_title' => 'Ngành đại học gợi ý',
                'result_vocational_title' => 'Ngành học nghề gợi ý (Ausbildung)',
                'result_contact_title' => 'Tư vấn chọn ngành và trường',
                'result_contact_text' => 'Chuyên viên tư vấn sẽ liên hệ với bạn qua điện thoại hoặc email để trao đổi về ngành học, trường và lộ trình du học Đức phù hợp. Nếu cần hỏi ngay, bạn có thể gọi điện hoặc nhắn Zalo.',
                'result_call_text' => 'Gọi tư vấn',
                'result_zalo_text' => 'Nhắn Zalo',
                'result_disclaimer' => 'Kết quả chỉ mang tính tham khảo. Khi chọn ngành, bạn nên cân nhắc thêm năng lực học tập, điều kiện gia đình và ý kiến của chuyên viên tư vấn.',
                'result_retake' => 'Làm lại bài trắc nghiệm',
            ],
            'email' => [
                'email_subject' => 'Kết quả trắc nghiệm Holland của bạn',
                'email_greeting' => 'Chào',
                'email_intro' => 'Cảm ơn bạn đã làm bài trắc nghiệm Holland. Mã sở thích nghề nghiệp của bạn là:',
                'email_button_text' => 'Xem đầy đủ kết quả',
                'email_closing' => 'Chuyên viên tư vấn sẽ liên hệ với bạn trong thời gian sớm nhất để trao đổi về ngành học, trường và lộ trình du học phù hợp.',
                'email_signature' => "Trân trọng,\nĐội ngũ tư vấn Viger-Edu",
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
