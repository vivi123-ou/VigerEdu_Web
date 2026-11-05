<?php

/*
|--------------------------------------------------------------------------
| Menu trái trang quản trị (theo docs/anh_mau_quan_tri/H19_admin_holland.png)
|--------------------------------------------------------------------------
|
| Đây là chữ của giao diện quản trị (không phải nội dung khách thấy).
| 'stage' = giai đoạn sẽ làm; mục chưa làm hiện trang "Đang xây dựng".
| 'help' = nội dung nút "?" của trang.
|
*/

return [
    [
        'items' => [
            ['key' => 'dashboard', 'label' => 'Bảng điều khiển', 'route' => 'admin.dashboard', 'icon' => 'home',
                'help' => 'Xem nhanh số câu hỏi đang bật, số lượt làm bài và các việc cần chú ý.'],
        ],
    ],
    [
        'title' => 'Bài test',
        'items' => [
            ['key' => 'question-sets', 'label' => 'Bộ câu hỏi (Phần B)', 'stage' => 3, 'icon' => 'list',
                'help' => 'Thêm, sửa, bật/tắt câu hỏi Holland; tạo nhiều bộ câu (THPT, Sinh viên…); đổi kiểu trả lời Đúng/Không hoặc thang 3–5 mức.'],
            ['key' => 'info-form', 'label' => 'Form thông tin khách (Phần A)', 'stage' => 3, 'icon' => 'form',
                'help' => 'Thêm, sửa, ẩn câu hỏi thông tin khách (họ tên, SĐT, lớp…) và chọn "Gửi sang CRM vào trường…".'],
            ['key' => 'scoring', 'label' => 'Cấu hình chấm điểm', 'stage' => 3, 'icon' => 'sliders',
                'help' => 'Trọng số khớp mã / khớp điểm, ngưỡng và tên nhãn, số ngành gợi ý. Có nút "Chạy thử".'],
            ['key' => 'groups', 'label' => 'Mô tả 6 nhóm & kết quả', 'stage' => 3, 'icon' => 'hexagon',
                'help' => 'Sửa chữ giải thích 6 nhóm R, I, A, S, E, C hiện trên trang kết quả.'],
        ],
    ],
    [
        'title' => 'Nội dung trang',
        'items' => [
            ['key' => 'intro', 'label' => 'Trang giới thiệu & Banner', 'stage' => 3, 'icon' => 'image',
                'help' => 'Đổi banner, tiêu đề, mô tả, chữ trên nút "Bắt đầu" và các khối nội dung trang giới thiệu.'],
            ['key' => 'result-page', 'label' => 'Trang kết quả', 'stage' => 3, 'icon' => 'chart',
                'help' => 'Đổi lời chào, lời mời đặt lịch, câu cảnh báo trên trang kết quả.'],
            ['key' => 'email', 'label' => 'Email gửi khách', 'stage' => 3, 'icon' => 'mail',
                'help' => 'Đổi tiêu đề, lời mở đầu và chữ ký của email gửi kết quả cho khách.'],
            ['key' => 'embed', 'label' => 'Khối nhúng cho web chính', 'stage' => 3, 'icon' => 'code',
                'help' => 'Nội dung khối "Test Holland" hiện trên web chính và đoạn mã nhúng.'],
            ['key' => 'appearance', 'label' => 'Menu, chân trang, giao diện', 'stage' => 3, 'icon' => 'palette',
                'help' => 'Logo, màu chủ đạo, menu đầu trang, thông tin chân trang (hotline, địa chỉ, mạng xã hội).'],
        ],
    ],
    [
        'title' => 'Dữ liệu',
        'items' => [
            ['key' => 'attempts', 'label' => 'Lượt làm bài', 'stage' => 3, 'icon' => 'users',
                'help' => 'Danh sách lượt làm bài, xem bài làm từng câu và bảng xếp hạng ngành. Không có danh sách khách (xem ở CRM).'],
            ['key' => 'outbox', 'label' => 'Hàng đợi gửi CRM', 'stage' => 4, 'icon' => 'send',
                'help' => 'Các lượt chưa gửi được sang CRM (CRM tắt hoặc lỗi) và nút gửi lại.'],
            ['key' => 'excel', 'label' => 'Xuất / nhập Excel', 'stage' => 7, 'icon' => 'table',
                'help' => 'Tải bộ câu hỏi ra Excel, sửa rồi nhập lại (có xem trước lỗi); xuất kết quả làm bài.'],
        ],
    ],
    [
        'title' => 'Cấu hình',
        'items' => [
            ['key' => 'connections', 'label' => 'Kết nối CRM & chatbot', 'stage' => 4, 'icon' => 'link',
                'help' => 'Bật/tắt gửi khách sang CRM, nhập địa chỉ và mã kết nối, nút "Kiểm tra kết nối"; dán mã nhúng chatbot.'],
            ['key' => 'settings', 'label' => 'Cài đặt chung & SEO', 'stage' => 7, 'icon' => 'settings',
                'help' => 'Tên đơn vị, hotline, Zalo, email gửi đi, tiêu đề và mô tả trang cho Google.'],
            ['key' => 'accounts', 'label' => 'Tài khoản & Sao lưu', 'stage' => 6, 'icon' => 'shield',
                'help' => 'Thêm tài khoản quản trị, phân vai trò; sao lưu và khôi phục dữ liệu.'],
        ],
    ],
];
