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
