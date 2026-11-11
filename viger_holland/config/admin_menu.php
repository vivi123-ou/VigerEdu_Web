<?php

/*
|--------------------------------------------------------------------------
| Menu trái trang quản trị – VIGER EDU Career Map
|--------------------------------------------------------------------------
|
| Đây là chữ của giao diện quản trị (không phải nội dung khách thấy).
| 'stage' = giai đoạn sẽ làm; mục chưa làm hiện trang "Đang xây dựng".
| 'help' = nội dung nút "?" của trang.
| 'editor' => true: tài khoản Marketing (EDITOR) vào được mục này.
|
*/

return [
    [
        'items' => [
            ['key' => 'dashboard', 'label' => 'Bảng điều khiển', 'route' => 'admin.dashboard', 'icon' => 'home', 'editor' => true,
                'help' => 'Xem nhanh tình trạng thư viện câu hỏi, số lượt làm bài và các việc hay làm.'],
        ],
    ],
    [
        'title' => 'Bài trắc nghiệm',
        'items' => [
            ['key' => 'question-bank', 'label' => 'Thư viện câu hỏi', 'stage' => 4, 'icon' => 'list',
                'help' => 'Thư viện 300 câu (6 nhóm × 50 câu). Mỗi lượt làm bài, phần mềm rút ngẫu nhiên 6 câu mỗi nhóm theo trọng số. Ở đây bạn duyệt, tạm ngưng, sửa câu, đổi trọng số và xem thống kê từng câu.'],
            ['key' => 'test-settings', 'label' => 'Cài đặt bài test', 'stage' => 1, 'icon' => 'sliders',
                'help' => 'Các con số của bài test và thuật toán: số câu mỗi nhóm, trọng số loại A/B/C, ngưỡng đọc chữ thứ ba (4,2), mức sát điểm, nhãn mức độ quan tâm, số ngành hiện cho khách.'],
        ],
    ],
    [
        'title' => 'Danh mục Holland',
        'items' => [
            ['key' => 'codes', 'label' => '6 nhóm, 15 cặp, 120 mã', 'stage' => 5, 'icon' => 'hexagon',
                'help' => 'Tên và mô tả 6 nhóm, 15 cặp nền (ngành, nghề, môn nền), 6 hướng chuyên sâu của chữ thứ ba. 120 mã ba chữ được phần mềm tự ghép, sửa riêng từng mã khi cần.'],
            ['key' => 'majors', 'label' => 'Ngành học & nghề', 'stage' => 5, 'icon' => 'chart',
                'help' => 'Danh mục ngành học tại Đức (mã Holland, nguồn), nghề nghiệp, môi trường làm việc và liên kết ngành – nghề.'],
        ],
    ],
    [
        'title' => 'Khách hàng',
        'items' => [
            ['key' => 'form-fields', 'label' => 'Trường thông tin khách', 'stage' => 5, 'icon' => 'form',
                'help' => 'Các ô khách điền trước khi làm bài (họ tên, SĐT, năm sinh, trường, điểm học bạ…). Chọn ô nào bắt buộc, ô nào gửi sang CRM, ô nào hiện trên báo cáo.'],
            ['key' => 'attempts', 'label' => 'Lượt làm bài', 'stage' => 6, 'icon' => 'users',
                'help' => 'Danh sách lượt làm bài và báo cáo nội bộ đầy đủ cho tư vấn viên.'],
        ],
    ],
    [
        'title' => 'Nội dung trang',
        'items' => [
            ['key' => 'intro', 'label' => 'Trang giới thiệu', 'route' => 'admin.page-editor.show', 'params' => ['page' => 'trang-gioi-thieu'], 'icon' => 'image', 'editor' => true,
                'help' => 'Trang mở ra đúng như khách thấy. Bấm vào chữ có viền chấm để gõ sửa, bấm vào ảnh để đổi ảnh, mỗi khối có nút Lên / Xuống / Ẩn. Sửa xong bấm "Lưu thay đổi" trên thanh đen. Muốn thêm khối mới hoặc thêm / bớt câu hỏi thường gặp: bấm "Thêm / bớt dòng…" trên khối, hoặc vào mục này rồi chọn tab "Các khối nội dung".'],
            ['key' => 'report-pages', 'label' => 'Trang báo cáo & email', 'stage' => 3, 'icon' => 'mail', 'editor' => true,
                'help' => 'Chữ trên trang báo cáo khách xem và email gửi kết quả.'],
            ['key' => 'embed', 'label' => 'Nhúng vào web chính', 'route' => 'admin.content.edit', 'params' => ['page' => 'embed'], 'icon' => 'code', 'editor' => true,
                'help' => 'Nội dung khối "Trắc nghiệm Holland" hiện trên web chính, tên miền được phép nhúng và đoạn mã để gửi người làm web chính.'],
            ['key' => 'appearance', 'label' => 'Giao diện & chân trang', 'route' => 'admin.content.edit', 'params' => ['page' => 'appearance'], 'icon' => 'palette',
                'help' => 'Tên đơn vị, logo, màu, font chữ, menu đầu trang, thông tin chân trang (hotline, Zalo, địa chỉ, mạng xã hội).'],
            ['key' => 'media', 'label' => 'Thư viện ảnh', 'route' => 'admin.media.index', 'icon' => 'image', 'editor' => true,
                'help' => 'Tải ảnh lên (tự thu nhỏ, nén), xem lại ảnh đã có. Ở các trang nội dung, bấm "Chọn ảnh" để lấy ảnh từ đây.'],
        ],
    ],
    [
        'title' => 'Cấu hình',
        'items' => [
            ['key' => 'connections', 'label' => 'Kết nối CRM & chatbot', 'stage' => 7, 'icon' => 'link',
                'help' => 'Bật/tắt gửi khách sang CRM, nhập địa chỉ và mã kết nối, nút "Kiểm tra kết nối"; dán mã nhúng chatbot.'],
            ['key' => 'settings', 'label' => 'Cài đặt chung & SEO', 'stage' => 7, 'icon' => 'settings',
                'help' => 'Tên đơn vị, hotline, Zalo, email gửi đi, tiêu đề và mô tả trang cho Google.'],
            ['key' => 'accounts', 'label' => 'Tài khoản & Sao lưu', 'stage' => 7, 'icon' => 'shield',
                'help' => 'Thêm tài khoản quản trị, tư vấn viên; phân vai trò; sao lưu và khôi phục dữ liệu.'],
        ],
    ],
];
