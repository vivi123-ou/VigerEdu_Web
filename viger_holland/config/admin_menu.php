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
            ['key' => 'question-sets', 'label' => 'Bộ câu hỏi (Phần B)', 'route' => 'admin.question-sets.index', 'icon' => 'list',
                'help' => 'Mỗi bộ câu là một bài test (ví dụ THPT, Sinh viên). Bấm tên bộ để thêm, sửa, tắt, kéo thả sắp xếp câu hỏi. "Dán nhiều câu" để thêm cả loạt (mỗi dòng một câu, đầu dòng ghi nhóm, ví dụ "R | Tôi thích sửa xe"). "Nhân bản" để tạo bộ mới từ bộ cũ. Mỗi bộ có link làm bài riêng để gửi khách. Xóa nhầm thì khôi phục ở mục Thùng rác cuối trang.'],
            ['key' => 'info-form', 'label' => 'Form thông tin khách (Phần A)', 'route' => 'admin.info-questions.index', 'icon' => 'form',
                'help' => 'Các câu khách điền ở bước cuối "Nhận kết quả". Bấm "+ Thêm câu", chọn kiểu trả lời và "Gửi sang CRM vào trường…" (trường có sẵn hoặc tự đặt mã mới). Câu mới hiện ngay trên form và tự gửi sang CRM. "Chỉ hiện khi…" để chỉ hỏi một nhóm khách. Kéo thả biểu tượng ⋮⋮ rồi bấm "Lưu thứ tự" để sắp xếp.'],
            ['key' => 'scoring', 'label' => 'Cấu hình chấm điểm', 'route' => 'admin.scoring.edit', 'icon' => 'sliders',
                'help' => 'Đổi trọng số, ngưỡng và tên nhãn, số ngành gợi ý. Bấm "Chạy thử" để xem một học sinh mẫu được gợi ý ngành nào với giá trị mới (chưa lưu) so với hiện tại. Hài lòng thì bấm "Lưu". Lượt làm bài cũ không bị chấm lại. Sửa nhầm thì bấm "Quay lại bản này" ở Lịch sử thay đổi.'],
            ['key' => 'groups', 'label' => 'Mô tả 6 nhóm Holland', 'route' => 'admin.groups.edit', 'icon' => 'hexagon',
                'help' => 'Tên và đoạn giải thích 6 nhóm R, I, A, S, E, C. Chữ này hiện trên trang kết quả và email gửi khách.'],
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
            ['key' => 'attempts', 'label' => 'Lượt làm bài', 'route' => 'admin.attempts.index', 'icon' => 'users',
                'help' => 'Danh sách lượt làm bài, xem bài làm từng câu và bảng xếp hạng ngành. Không có danh sách khách (xem ở CRM).'],
            ['key' => 'outbox', 'label' => 'Hàng đợi gửi CRM', 'stage' => 4, 'icon' => 'send',
                'help' => 'Các lượt chưa gửi được sang CRM (CRM tắt hoặc lỗi) và nút gửi lại.'],
            ['key' => 'excel', 'label' => 'Xuất / nhập Excel', 'route' => 'admin.excel.index', 'icon' => 'table',
                'help' => 'Bấm "Tải bộ câu hỏi" để lấy file Excel, sửa trong Excel rồi "Nhập bộ câu hỏi". Trước khi lưu luôn có bước xem trước: dòng nào sai được báo rõ và chưa ghi gì cả. Câu xóa khỏi file chỉ bị tắt, không mất. (Xuất kết quả làm bài: sắp có.)'],
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
