<?php

/*
|--------------------------------------------------------------------------
| Các trang "Nội dung trang" trong admin (giai đoạn 3c)
|--------------------------------------------------------------------------
|
| Mỗi trang là một danh sách ô, giá trị lưu trong bảng `settings` (khóa = 'key').
| Thêm ô mới: thêm một dòng ở đây, không phải sửa controller hay view admin.
|
| type: text | textarea | rich (trình soạn thảo, lọc HTML khi lưu) | image (thư viện ảnh) | color | font
|       | url | email | links (danh sách link: chữ + địa chỉ) | domains (mỗi dòng một tên miền)
| preview: trang khách dùng cho nút "Xem trước" (home, embed). Trang kết quả, email: làm lại ở GĐ3.
|
*/

return [
    'intro' => [
        'title' => 'Trang mở đầu – Banner',
        'menu' => 'intro',
        'group' => 'intro',
        'preview' => 'home',
        'sections' => [
            'Banner' => [
                ['key' => 'intro_eyebrow', 'label' => 'Dòng chữ nhỏ trên tiêu đề', 'type' => 'text', 'hint' => 'Ví dụ: Trắc nghiệm hướng nghiệp Holland.'],
                ['key' => 'intro_title', 'label' => 'Tiêu đề lớn', 'type' => 'text', 'required' => true],
                ['key' => 'intro_title_accent', 'label' => 'Phần cuối tiêu đề (chữ nghiêng màu đỏ)', 'type' => 'text', 'hint' => 'Ví dụ: du học Đức? Để trống nếu không cần.'],
                ['key' => 'intro_subtitle', 'label' => 'Đoạn giới thiệu', 'type' => 'textarea', 'hint' => '2–3 câu, nói bài test giúp gì cho người đọc. Gõ {so_cau} để tự hiện số câu của đề (theo "Cách bốc đề & chấm điểm").'],
                ['key' => 'intro_benefit_1', 'label' => 'Lợi ích 1', 'type' => 'text', 'hint' => 'Hiện kèm dấu tick. Để trống để ẩn.'],
                ['key' => 'intro_benefit_2', 'label' => 'Lợi ích 2', 'type' => 'text'],
                ['key' => 'intro_benefit_3', 'label' => 'Lợi ích 3', 'type' => 'text'],
                ['key' => 'intro_button_text', 'label' => 'Chữ trên nút bắt đầu', 'type' => 'text', 'required' => true],
                ['key' => 'intro_button_note', 'label' => 'Dòng chữ nhỏ dưới nút', 'type' => 'text'],
                ['key' => 'banner_image', 'label' => 'Ảnh banner (máy tính)', 'type' => 'image', 'hint' => 'Có ảnh thì ảnh thay cho "Báo cáo mẫu" bên phải tiêu đề. Nên rộng khoảng 1200px. Để trống thì hiện báo cáo mẫu.'],
                ['key' => 'banner_image_mobile', 'label' => 'Ảnh banner (điện thoại)', 'type' => 'image', 'hint' => 'Hiện trên tiêu đề khi xem bằng điện thoại. Để trống = dùng ảnh máy tính.'],
            ],
            'Báo cáo mẫu (bên phải tiêu đề)' => [
                ['key' => 'intro_sample_label', 'label' => 'Dòng chữ trên khung', 'type' => 'text'],
                ['key' => 'intro_sample_code', 'label' => 'Mã Holland làm ví dụ', 'type' => 'text', 'hint' => '2 chữ, ví dụ IS. Tên nhóm và ngành tự lấy từ danh mục.'],
                ['key' => 'intro_sample_text', 'label' => 'Câu giải thích mẫu', 'type' => 'textarea'],
                ['key' => 'intro_sample_majors_heading', 'label' => 'Tiêu đề phần ngành', 'type' => 'text'],
            ],
        ],
    ],

    'embed' => [
        'title' => 'Ô giới thiệu trên viger.edu.vn',
        'menu' => 'embed',
        'group' => 'embed',
        'preview' => 'embed',
        'sections' => [
            'Nội dung khối' => [
                ['key' => 'embed_title', 'label' => 'Tiêu đề', 'type' => 'text', 'required' => true],
                ['key' => 'embed_subtitle', 'label' => 'Mô tả ngắn', 'type' => 'textarea'],
                ['key' => 'embed_image', 'label' => 'Ảnh', 'type' => 'image', 'hint' => 'Không bắt buộc. Nên rộng khoảng 800px.'],
                ['key' => 'embed_button_text', 'label' => 'Chữ trên nút', 'type' => 'text', 'required' => true],
            ],
            'Web nào được hiện ô này' => [
                ['key' => 'embed_allowed_domains', 'label' => 'Địa chỉ web được hiện ô giới thiệu', 'type' => 'domains', 'hint' => 'Mỗi dòng một địa chỉ, ví dụ https://viger.edu.vn. Web khác chép mã về cũng không hiện được (chống giả mạo).'],
            ],
        ],
    ],

    'report' => [
        'title' => 'Chữ trên báo cáo & email',
        'menu' => 'report-pages',
        'group' => 'report',
        'sections' => [
            'Trang làm bài' => [
                ['key' => 'quiz_legend_title', 'label' => 'Tiêu đề ô chú thích số sao', 'type' => 'text'],
                ['key' => 'quiz_missing_text', 'label' => 'Chữ cạnh câu chưa chọn', 'type' => 'text', 'hint' => 'Ví dụ: Chưa chọn câu trả lời.'],
                ['key' => 'quiz_missing_alert', 'label' => 'Lời nhắc khi còn câu chưa chọn', 'type' => 'textarea'],
                ['key' => 'quiz_prev_text', 'label' => 'Chữ nút quay lại', 'type' => 'text', 'required' => true],
                ['key' => 'quiz_next_text', 'label' => 'Chữ nút sang trang sau', 'type' => 'text', 'required' => true],
                ['key' => 'quiz_finish_text', 'label' => 'Chữ nút ở trang cuối', 'type' => 'text', 'required' => true],
            ],
            'Kết quả sơ lược' => [
                ['key' => 'result_kicker', 'label' => 'Dòng chữ nhỏ trên mã Holland', 'type' => 'text'],
                ['key' => 'result_majors_heading', 'label' => 'Tiêu đề phần ngành', 'type' => 'text'],
                ['key' => 'result_pair_label', 'label' => 'Nhãn của ngành nền của cặp mã', 'type' => 'text', 'hint' => 'Ví dụ: Gợi ý chính. Dùng cả ở báo cáo đầy đủ.'],
                ['key' => 'result_more_heading', 'label' => 'Tiêu đề phần "Báo cáo đầy đủ giúp bạn hiểu"', 'type' => 'text'],
                ['key' => 'result_more_1', 'label' => 'Ý 1', 'type' => 'text', 'hint' => 'Để trống để ẩn.'],
                ['key' => 'result_more_2', 'label' => 'Ý 2', 'type' => 'text'],
                ['key' => 'result_more_3', 'label' => 'Ý 3', 'type' => 'text'],
                ['key' => 'result_more_4', 'label' => 'Ý 4', 'type' => 'text'],
                ['key' => 'result_button_text', 'label' => 'Chữ nút xem báo cáo đầy đủ', 'type' => 'text', 'required' => true],
            ],
            'Form thông tin' => [
                ['key' => 'report_form_title', 'label' => 'Tiêu đề form', 'type' => 'text', 'required' => true],
                ['key' => 'report_form_button', 'label' => 'Chữ nút gửi form', 'type' => 'text', 'required' => true],
                ['key' => 'report_consent_text', 'label' => 'Câu đồng ý dưới nút', 'type' => 'textarea', 'hint' => 'Để trống thì không hiện (đang để trống theo quyết định 18/11). Có câu thì khách bấm nút là đồng ý, và câu được lưu lại.'],
                ['key' => 'report_consent_version', 'label' => 'Mã phiên bản câu đồng ý', 'type' => 'text', 'hint' => 'Ví dụ: dieu-khoan-2026-11. Đổi câu đồng ý thì đổi mã này.'],
                ['key' => 'report_bot_error', 'label' => 'Báo lỗi khi kiểm tra chống bot không qua', 'type' => 'text'],
            ],
            'Mã xác nhận' => [
                ['key' => 'otp_title', 'label' => 'Tiêu đề trang nhập mã', 'type' => 'text', 'required' => true],
                ['key' => 'otp_sent_text', 'label' => 'Câu báo đã gửi mã', 'type' => 'text', 'hint' => 'Giữ {email}: tự thay bằng email đã che bớt.'],
                ['key' => 'otp_spam_hint', 'label' => 'Nhắc xem thư rác', 'type' => 'text'],
                ['key' => 'otp_button', 'label' => 'Chữ nút xem báo cáo', 'type' => 'text', 'required' => true],
                ['key' => 'otp_resend_text', 'label' => 'Chữ nút gửi lại mã', 'type' => 'text', 'required' => true],
                ['key' => 'otp_edit_email', 'label' => 'Chữ link sửa email', 'type' => 'text', 'required' => true],
                ['key' => 'otp_wrong', 'label' => 'Báo mã sai', 'type' => 'text', 'hint' => 'Giữ {so_lan}: số lần còn được thử.'],
                ['key' => 'otp_expired', 'label' => 'Báo mã hết hạn', 'type' => 'text'],
                ['key' => 'otp_locked', 'label' => 'Báo bị khóa do nhập sai nhiều lần', 'type' => 'text', 'hint' => 'Giữ {phut}: số phút phải chờ.'],
                ['key' => 'otp_format_error', 'label' => 'Báo nhập thiếu số', 'type' => 'text'],
                ['key' => 'otp_wait_error', 'label' => 'Báo phải chờ mới gửi lại được', 'type' => 'text', 'hint' => 'Giữ {giay}: số giây còn phải chờ.'],
                ['key' => 'otp_hourly_error', 'label' => 'Báo yêu cầu quá nhiều mã', 'type' => 'text'],
                ['key' => 'otp_send_error', 'label' => 'Báo không gửi được email', 'type' => 'text'],
                ['key' => 'otp_resent', 'label' => 'Báo đã gửi mã mới', 'type' => 'text'],
            ],
            'Email gửi mã' => [
                ['key' => 'otp_email_subject', 'label' => 'Tiêu đề email', 'type' => 'text', 'required' => true, 'hint' => 'Giữ {ma}: tự thay bằng mã 6 số.'],
                ['key' => 'otp_email_intro', 'label' => 'Câu mở đầu', 'type' => 'text'],
                ['key' => 'otp_email_expiry', 'label' => 'Câu báo thời hạn của mã', 'type' => 'text', 'hint' => 'Giữ {phut}: số phút mã còn hiệu lực.'],
                ['key' => 'otp_email_ignore', 'label' => 'Câu cuối email', 'type' => 'text'],
            ],
            'Báo cáo đầy đủ' => [
                ['key' => 'report_kicker', 'label' => 'Dòng chữ nhỏ trên tiêu đề', 'type' => 'text'],
                ['key' => 'report_opening', 'label' => 'Câu mở đầu', 'type' => 'text', 'hint' => 'Giữ {nhom1}, {nhom2}: tự thay bằng tên 2 nhóm nổi bật.'],
                ['key' => 'report_sec1', 'label' => 'Tiêu đề phần 1 (nhóm sở thích)', 'type' => 'text'],
                ['key' => 'report_sec2', 'label' => 'Tiêu đề phần 2 (mức độ 6 nhóm)', 'type' => 'text'],
                ['key' => 'report_sec3', 'label' => 'Tiêu đề phần 3 (ngành học)', 'type' => 'text'],
                ['key' => 'report_sec4', 'label' => 'Tiêu đề phần 4 (bước tiếp theo)', 'type' => 'text'],
                ['key' => 'report_first_heading', 'label' => 'Nhãn nhóm thứ nhất', 'type' => 'text'],
                ['key' => 'report_second_heading', 'label' => 'Nhãn nhóm thứ hai', 'type' => 'text'],
                ['key' => 'report_third_heading', 'label' => 'Nhãn chữ thứ ba', 'type' => 'text'],
                ['key' => 'report_combine_heading', 'label' => 'Tiêu đề ô "khi hai nhóm kết hợp"', 'type' => 'text'],
                ['key' => 'report_why_label', 'label' => 'Tiêu đề cột lý do', 'type' => 'text'],
                ['key' => 'report_jobs_label', 'label' => 'Tiêu đề cột nghề', 'type' => 'text'],
                ['key' => 'report_why_pair', 'label' => 'Lý do cho ngành nền của cặp', 'type' => 'text', 'hint' => 'Giữ {cap}: tự thay bằng tên cặp, ví dụ Nghiên cứu – Xã hội.'],
                ['key' => 'report_why_catalog', 'label' => 'Lý do cho ngành khác', 'type' => 'text'],
                ['key' => 'report_next_title', 'label' => 'Tiêu đề phần bước tiếp theo', 'type' => 'text'],
                ['key' => 'report_next_text', 'label' => 'Đoạn dẫn phần bước tiếp theo', 'type' => 'textarea'],
                ['key' => 'report_next_topics_heading', 'label' => 'Câu dẫn các chủ đề trao đổi', 'type' => 'text'],
                ['key' => 'report_next_topic_1', 'label' => 'Chủ đề 1', 'type' => 'text', 'hint' => 'Để trống để ẩn.'],
                ['key' => 'report_next_topic_2', 'label' => 'Chủ đề 2', 'type' => 'text'],
                ['key' => 'report_next_topic_3', 'label' => 'Chủ đề 3', 'type' => 'text'],
                ['key' => 'report_zalo_button', 'label' => 'Chữ nút Zalo', 'type' => 'text', 'hint' => 'Nút chỉ hiện khi đã nhập số Zalo ở "Logo, màu, hotline, chân trang".'],
                ['key' => 'report_retake', 'label' => 'Chữ nút làm lại', 'type' => 'text'],
                ['key' => 'report_code_label', 'label' => 'Câu ghi mã báo cáo', 'type' => 'text', 'hint' => 'Giữ {ma}: mã 8 ký tự khách đọc cho chuyên viên để tra cứu.'],
                ['key' => 'report_once_note', 'label' => 'Ghi chú báo cáo chỉ xem trên thẻ này', 'type' => 'text'],
                ['key' => 'report_note', 'label' => 'Lưu ý cuối báo cáo', 'type' => 'textarea'],
                ['key' => 'report_tip', 'label' => 'Lời khuyên cuối báo cáo', 'type' => 'textarea'],
                ['key' => 'report_loading', 'label' => 'Chữ khi đang mở lại báo cáo', 'type' => 'text'],
            ],
            'Báo cáo đã đóng' => [
                ['key' => 'report_closed_title', 'label' => 'Tiêu đề', 'type' => 'text', 'required' => true],
                ['key' => 'report_closed_text', 'label' => 'Lời giải thích', 'type' => 'textarea'],
                ['key' => 'report_closed_button', 'label' => 'Chữ nút làm bài lại', 'type' => 'text', 'required' => true],
            ],
        ],
    ],

    'appearance' => [
        'title' => 'Logo, màu, hotline, chân trang',
        'menu' => 'appearance',
        'group' => 'general',
        'preview' => 'home',
        'sections' => [
            'Thương hiệu' => [
                ['key' => 'site_name', 'label' => 'Tên đơn vị', 'type' => 'text', 'required' => true],
                ['key' => 'brand_title', 'label' => 'Tên cạnh logo (đầu và chân trang)', 'type' => 'text', 'hint' => 'Ví dụ: VIGER.EDU.VN'],
                ['key' => 'site_tagline', 'label' => 'Dòng chữ dưới tên', 'type' => 'text', 'hint' => 'Ví dụ: From Vietnam to Europe'],
                ['key' => 'test_name', 'label' => 'Tên bài test', 'type' => 'text', 'required' => true, 'hint' => 'Hiện dưới tên đơn vị và trên tab trình duyệt.'],
                ['key' => 'logo_path', 'label' => 'Logo', 'type' => 'image', 'hint' => 'Ảnh vuông, nền trong suốt (PNG) là đẹp nhất.'],
                ['key' => 'favicon_path', 'label' => 'Biểu tượng trên tab trình duyệt (favicon)', 'type' => 'image', 'hint' => 'Ảnh vuông nhỏ, ví dụ 64×64px.'],
                ['key' => 'primary_color', 'label' => 'Màu chủ đạo', 'type' => 'color', 'hint' => 'Màu nút bấm và điểm nhấn.'],
                ['key' => 'text_color', 'label' => 'Màu chữ', 'type' => 'color'],
                ['key' => 'font_body', 'label' => 'Font chữ nội dung', 'type' => 'font'],
                ['key' => 'font_heading', 'label' => 'Font chữ tiêu đề', 'type' => 'font'],
            ],
            'Về web chính' => [
                ['key' => 'main_site_url', 'label' => 'Địa chỉ web chính', 'type' => 'url', 'hint' => 'Bấm logo thì về địa chỉ này; chân trang có link quay về.'],
                ['key' => 'main_site_link_text', 'label' => 'Chữ link quay về web chính', 'type' => 'text'],
            ],
            'Chân trang' => [
                ['key' => 'hotline', 'label' => 'Hotline', 'type' => 'text', 'hint' => 'Hiện ở chân trang và nút "Gọi tư vấn" trên trang kết quả. Để trống để ẩn.'],
                ['key' => 'zalo', 'label' => 'Số Zalo', 'type' => 'text', 'hint' => 'Hiện nút "Nhắn Zalo". Để trống để ẩn.'],
                ['key' => 'email', 'label' => 'Email liên hệ', 'type' => 'email'],
                ['key' => 'address', 'label' => 'Địa chỉ', 'type' => 'textarea'],
                ['key' => 'copyright', 'label' => 'Dòng bản quyền', 'type' => 'text', 'hint' => 'Năm hiện tại tự thêm phía trước.'],
            ],
        ],
    ],
];
