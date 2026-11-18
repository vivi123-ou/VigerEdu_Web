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
            'report' => [
                // Form nhận báo cáo, mã xác nhận, email, báo cáo đầy đủ (H4); sửa ở "Chữ trên báo cáo & email"
                'report_form_title' => 'Cập nhật thông tin để xem báo cáo đầy đủ',
                'report_form_button' => 'Xem báo cáo',
                'report_consent_text' => '', // Vân chốt 18/11: không có câu đồng ý. Gõ câu vào đây thì câu hiện dưới nút và được lưu lại.
                'report_consent_version' => '',
                'report_bot_error' => 'Chưa kiểm tra được trình duyệt. Bạn tải lại trang rồi thử lại.',
                'otp_title' => 'Nhập mã xác nhận',
                'otp_sent_text' => 'Mã 6 số đã gửi tới {email}.',
                'otp_spam_hint' => 'Không thấy email? Bạn xem cả mục Thư rác / Quảng cáo.',
                'otp_button' => 'Xem báo cáo',
                'otp_resend_text' => 'Gửi lại mã',
                'otp_edit_email' => 'Sửa email',
                'otp_wrong' => 'Mã chưa đúng. Bạn còn {so_lan} lần thử.',
                'otp_expired' => 'Mã đã hết hạn. Bạn bấm "Gửi lại mã" để nhận mã mới.',
                'otp_locked' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau {phut} phút.',
                'otp_format_error' => 'Vui lòng nhập đủ 6 số.',
                'otp_hourly_error' => 'Bạn đã yêu cầu nhiều mã. Vui lòng thử lại sau ít phút.',
                'otp_wait_error' => 'Vui lòng chờ {giay} giây rồi gửi lại mã.',
                'otp_send_error' => 'Chưa gửi được mã. Bạn kiểm tra lại email hoặc thử lại sau ít phút.',
                'otp_resent' => 'Đã gửi mã mới. Bạn kiểm tra hộp thư (cả mục Thư rác / Quảng cáo).',
                'otp_ttl_minutes' => '5',
                'otp_max_attempts' => '5',
                'otp_lock_minutes' => '30',
                'otp_resend_seconds' => '60',
                'otp_max_per_hour' => '5',
                'otp_email_subject' => 'Mã xác nhận của bạn: {ma}',
                'otp_email_intro' => 'Mã xác nhận để mở báo cáo Holland của bạn là:',
                'otp_email_expiry' => 'Mã có hiệu lực trong {phut} phút.',
                'otp_email_ignore' => 'Nếu bạn không làm bài trắc nghiệm, hãy bỏ qua email này.',
                'report_kicker' => 'Báo cáo sở thích nghề nghiệp',
                'report_opening' => 'Kết quả của bạn nổi bật ở hai nhóm {nhom1} và {nhom2}.',
                'report_sec1' => 'Nhóm sở thích của bạn',
                'report_sec2' => 'Mức độ quan tâm theo 6 nhóm',
                'report_sec3' => 'Ngành học tại Đức phù hợp với bạn',
                'report_sec4' => 'Bước tiếp theo',
                'report_first_heading' => 'Nhóm nổi bật nhất',
                'report_second_heading' => 'Nhóm thứ hai',
                'report_combine_heading' => 'Khi hai nhóm kết hợp',
                'report_third_heading' => 'Chữ thứ ba',
                'report_third_text' => 'Nhóm {nhom3} của bạn cũng ở mức cao. Bạn có thể tìm hiểu thêm hướng chuyên sâu {chuyen_sau}, với các kỹ năng như {ky_nang}.',
                'report_relation_1' => 'Hai nhóm {nhom1} và {nhom2} nằm cạnh nhau trên lục giác Holland, nên khá gần nhau và dễ kết hợp trong cùng một ngành, một công việc.',
                'report_relation_2' => 'Hai nhóm {nhom1} và {nhom2} cách nhau một bậc trên lục giác Holland: khác nhau nhưng bổ sung cho nhau.',
                'report_relation_3' => 'Hai nhóm {nhom1} và {nhom2} nằm đối diện nhau trên lục giác Holland nên ít khi đi cùng nhau. Trao đổi với chuyên viên sẽ giúp bạn tìm ngành kết hợp được cả hai.',
                'report_why_label' => 'Vì sao được đề xuất',
                'report_jobs_label' => 'Nghề tham khảo',
                'report_why_pair' => 'Ngành nền của cặp {cap}.',
                'report_why_catalog' => 'Mã Holland của ngành gần với sở thích của bạn.',
                'report_occupations_per_major' => '3',
                'report_next_title' => 'Hiểu sở thích là bước đầu. Chọn đúng lộ trình mới là điều quan trọng.',
                'report_next_text' => 'Kết quả Holland giúp bạn nhận ra lĩnh vực mình quan tâm. Chuyên viên VIGER EDU có thể cùng bạn xem thêm điều kiện học tập và chọn ngành học tại Đức.',
                'report_next_topics_heading' => 'Khi trao đổi với chuyên viên, bạn có thể hỏi về:',
                'report_next_topic_1' => 'Ngành học và trường phù hợp tại Đức',
                'report_next_topic_2' => 'Điều kiện đầu vào, ngoại ngữ và chi phí',
                'report_next_topic_3' => 'Lộ trình học tập và định hướng sau tốt nghiệp',
                'report_zalo_button' => 'Nhắn Zalo để trao đổi thêm',
                'report_retake' => 'Làm lại với câu hỏi mới',
                'report_once_note' => 'Báo cáo chỉ xem được trên thẻ (tab) này. Đóng thẻ là báo cáo đóng.',
                'report_loading' => 'Đang mở báo cáo…',
                'report_code_label' => 'Mã báo cáo của bạn: {ma}. Đọc mã này khi trao đổi với chuyên viên.',
                'contacts_days' => '7', // "Khách cần liên lạc" hiện khách của bấy nhiêu ngày gần nhất (không ghi ra trên trang của nhân viên)
                'report_note' => 'Lưu ý: Kết quả mang tính tham khảo. Holland đo sở thích, không đo năng lực và không bảo đảm khả năng trúng tuyển.',
                'report_tip' => 'Lời khuyên: tra tên ngành tiếng Đức trên Hochschulkompass để xem các trường đang đào tạo.',
                'report_closed_title' => 'Báo cáo đã đóng',
                'report_closed_text' => 'Để giữ riêng tư cho bạn, báo cáo chỉ xem được trên thẻ (tab) đã mở nó. Thẻ đã đóng thì báo cáo cũng đóng. Muốn xem lại, bạn làm bài lại với câu hỏi mới.',
                'report_closed_button' => 'Làm bài lại',
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
