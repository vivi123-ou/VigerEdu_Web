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
| preview: trang khách dùng cho nút "Xem trước" (home, result, email, embed).
|
*/

return [
    'intro' => [
        'title' => 'Trang giới thiệu – Banner',
        'menu' => 'intro',
        'group' => 'intro',
        'preview' => 'home',
        'sections' => [
            'Banner' => [
                ['key' => 'intro_eyebrow', 'label' => 'Dòng chữ nhỏ trên tiêu đề', 'type' => 'text', 'hint' => 'Ví dụ: Trắc nghiệm hướng nghiệp Holland.'],
                ['key' => 'intro_title', 'label' => 'Tiêu đề lớn', 'type' => 'text', 'required' => true],
                ['key' => 'intro_subtitle', 'label' => 'Đoạn giới thiệu', 'type' => 'textarea', 'hint' => '2–3 câu, nói bài test giúp gì cho người đọc.'],
                ['key' => 'intro_benefit_1', 'label' => 'Lợi ích 1', 'type' => 'text', 'hint' => 'Hiện kèm dấu tick. Để trống để ẩn.'],
                ['key' => 'intro_benefit_2', 'label' => 'Lợi ích 2', 'type' => 'text'],
                ['key' => 'intro_benefit_3', 'label' => 'Lợi ích 3', 'type' => 'text'],
                ['key' => 'intro_button_text', 'label' => 'Chữ trên nút bắt đầu', 'type' => 'text', 'required' => true],
                ['key' => 'intro_button_note', 'label' => 'Dòng chữ nhỏ dưới nút', 'type' => 'text'],
                ['key' => 'banner_image', 'label' => 'Ảnh banner (máy tính)', 'type' => 'image', 'hint' => 'Hiện bên phải tiêu đề trên máy tính. Nên rộng khoảng 1200px, tỉ lệ 4:3 hoặc 1:1. Để trống thì không có ảnh.'],
                ['key' => 'banner_image_mobile', 'label' => 'Ảnh banner (điện thoại)', 'type' => 'image', 'hint' => 'Hiện trên tiêu đề khi xem bằng điện thoại. Để trống = dùng ảnh máy tính.'],
            ],
        ],
    ],

    'result-page' => [
        'title' => 'Trang kết quả',
        'menu' => 'result-page',
        'group' => 'result',
        'preview' => 'result',
        'sections' => [
            'Phần đầu' => [
                ['key' => 'result_title', 'label' => 'Dòng chữ nhỏ trên lời chào', 'type' => 'text'],
                ['key' => 'result_greeting', 'label' => 'Lời chào', 'type' => 'text', 'hint' => 'Tên khách tự thêm phía sau, ví dụ "Chào An,".'],
                ['key' => 'result_intro', 'label' => 'Đoạn mở đầu', 'type' => 'textarea'],
                ['key' => 'result_code_label', 'label' => 'Chữ trên mã Holland', 'type' => 'text'],
            ],
            'Biểu đồ và gợi ý' => [
                ['key' => 'result_chart_title', 'label' => 'Tiêu đề biểu đồ 6 nhóm', 'type' => 'text'],
                ['key' => 'result_flat_note', 'label' => 'Câu nhắc khi điểm các nhóm khá đều', 'type' => 'textarea'],
                ['key' => 'result_tie_note', 'label' => 'Câu nhắc khi có nhóm bằng điểm', 'type' => 'textarea'],
                ['key' => 'result_fields_title', 'label' => 'Tiêu đề phần lĩnh vực', 'type' => 'text'],
                ['key' => 'result_university_title', 'label' => 'Tiêu đề phần ngành đại học', 'type' => 'text'],
                ['key' => 'result_vocational_title', 'label' => 'Tiêu đề phần học nghề', 'type' => 'text'],
            ],
            'Liên hệ tư vấn' => [
                ['key' => 'result_contact_title', 'label' => 'Tiêu đề khối liên hệ', 'type' => 'text'],
                ['key' => 'result_contact_text', 'label' => 'Nội dung khối liên hệ', 'type' => 'rich'],
                ['key' => 'result_call_text', 'label' => 'Chữ nút gọi điện', 'type' => 'text', 'hint' => 'Số hotline lấy ở trang "Giao diện & chân trang".'],
                ['key' => 'result_zalo_text', 'label' => 'Chữ nút Zalo', 'type' => 'text'],
                ['key' => 'result_disclaimer', 'label' => 'Lưu ý cuối trang', 'type' => 'textarea'],
                ['key' => 'result_retake', 'label' => 'Chữ link làm lại bài', 'type' => 'text'],
            ],
        ],
    ],

    'email' => [
        'title' => 'Email gửi khách',
        'menu' => 'email',
        'group' => 'email',
        'preview' => 'email',
        'sections' => [
            'Email kết quả' => [
                ['key' => 'email_subject', 'label' => 'Tiêu đề email', 'type' => 'text', 'required' => true],
                ['key' => 'email_greeting', 'label' => 'Lời chào', 'type' => 'text', 'hint' => 'Tên khách tự thêm phía sau.'],
                ['key' => 'email_intro', 'label' => 'Lời mở đầu (trước mã Holland)', 'type' => 'rich'],
                ['key' => 'email_button_text', 'label' => 'Chữ nút xem kết quả', 'type' => 'text'],
                ['key' => 'email_closing', 'label' => 'Lời kết', 'type' => 'rich'],
                ['key' => 'email_signature', 'label' => 'Chữ ký', 'type' => 'textarea', 'hint' => 'Mỗi dòng một dòng chữ ký.'],
            ],
        ],
    ],

    'embed' => [
        'title' => 'Khối nhúng cho web chính',
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
            'Tên miền được phép nhúng' => [
                ['key' => 'embed_allowed_domains', 'label' => 'Tên miền được nhúng khối này', 'type' => 'domains', 'hint' => 'Mỗi dòng một địa chỉ, ví dụ https://viger.edu.vn. Trang khác không nhúng được (chống giả mạo).'],
            ],
        ],
    ],

    'appearance' => [
        'title' => 'Menu, chân trang, giao diện',
        'menu' => 'appearance',
        'group' => 'general',
        'preview' => 'home',
        'sections' => [
            'Thương hiệu' => [
                ['key' => 'site_name', 'label' => 'Tên đơn vị (đầu trang)', 'type' => 'text', 'required' => true],
                ['key' => 'test_name', 'label' => 'Tên bài test', 'type' => 'text', 'required' => true, 'hint' => 'Hiện dưới tên đơn vị và trên tab trình duyệt.'],
                ['key' => 'logo_path', 'label' => 'Logo', 'type' => 'image', 'hint' => 'Ảnh vuông, nền trong suốt (PNG) là đẹp nhất.'],
                ['key' => 'favicon_path', 'label' => 'Biểu tượng trên tab trình duyệt (favicon)', 'type' => 'image', 'hint' => 'Ảnh vuông nhỏ, ví dụ 64×64px.'],
                ['key' => 'primary_color', 'label' => 'Màu chủ đạo', 'type' => 'color', 'hint' => 'Màu nút bấm và điểm nhấn.'],
                ['key' => 'text_color', 'label' => 'Màu chữ', 'type' => 'color'],
                ['key' => 'font_body', 'label' => 'Font chữ nội dung', 'type' => 'font'],
                ['key' => 'font_heading', 'label' => 'Font chữ tiêu đề', 'type' => 'font'],
            ],
            'Đầu trang' => [
                ['key' => 'header_menu', 'label' => 'Menu đầu trang', 'type' => 'links', 'hint' => 'Các link hiện ở đầu trang, ví dụ "Du học Đức" → https://viger.edu.vn/du-hoc-duc. Để trống nếu không cần menu.'],
                ['key' => 'main_site_url', 'label' => 'Địa chỉ web chính', 'type' => 'url', 'hint' => 'Nút quay về web chính ở góc phải đầu trang. Để trống để ẩn.'],
                ['key' => 'main_site_link_text', 'label' => 'Chữ nút quay về web chính', 'type' => 'text'],
            ],
            'Chân trang' => [
                ['key' => 'company_name', 'label' => 'Tên công ty', 'type' => 'text'],
                ['key' => 'footer_description', 'label' => 'Giới thiệu ngắn', 'type' => 'textarea'],
                ['key' => 'hotline', 'label' => 'Hotline', 'type' => 'text', 'hint' => 'Hiện ở chân trang và nút "Gọi tư vấn" trên trang kết quả. Để trống để ẩn.'],
                ['key' => 'zalo', 'label' => 'Số Zalo', 'type' => 'text', 'hint' => 'Hiện nút "Nhắn Zalo". Để trống để ẩn.'],
                ['key' => 'email', 'label' => 'Email liên hệ', 'type' => 'email'],
                ['key' => 'address', 'label' => 'Địa chỉ', 'type' => 'textarea'],
                ['key' => 'working_hours', 'label' => 'Giờ làm việc', 'type' => 'text', 'hint' => 'Ví dụ: Thứ 2 – Thứ 7, 8:00 – 17:30.'],
                ['key' => 'footer_links', 'label' => 'Mạng xã hội và link khác', 'type' => 'links', 'hint' => 'Ví dụ "Facebook" → https://facebook.com/…'],
                ['key' => 'copyright', 'label' => 'Dòng bản quyền', 'type' => 'text', 'hint' => 'Năm hiện tại tự thêm phía trước.'],
            ],
        ],
    ],
];
