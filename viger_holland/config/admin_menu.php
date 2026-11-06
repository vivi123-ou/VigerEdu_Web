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
            ['key' => 'dashboard', 'label' => 'Bảng điều khiển', 'route' => 'admin.dashboard', 'icon' => 'home', 'editor' => true,
                'help' => 'Xem nhanh số câu hỏi đang bật, số lượt làm bài và các việc cần chú ý.'],
        ],
    ],
    [
        'title' => 'Bài test',
        'items' => [
            ['key' => 'question-sets', 'label' => 'Bộ câu hỏi', 'route' => 'admin.question-sets.index', 'icon' => 'list',
                'help' => 'Mỗi bộ câu là một bài test (ví dụ THPT, Sinh viên). Bấm tên bộ để thêm, sửa, tắt, kéo thả sắp xếp câu hỏi. "Dán nhiều câu" để thêm cả loạt (mỗi dòng một câu, đầu dòng ghi nhóm, ví dụ "R | Tôi thích sửa xe"). "Nhân bản" để tạo bộ mới từ bộ cũ. Mỗi bộ có link làm bài riêng để gửi khách. Xóa nhầm thì khôi phục ở mục Thùng rác cuối trang.'],
            ['key' => 'info-form', 'label' => 'Form thông tin khách', 'route' => 'admin.info-questions.index', 'icon' => 'form',
                'help' => 'Các câu khách điền ở bước cuối "Nhận kết quả". Bấm "+ Thêm câu", chọn kiểu trả lời và "Gửi sang CRM vào trường…" (trường có sẵn hoặc tự đặt mã mới). Câu mới hiện ngay trên form và tự gửi sang CRM. "Chỉ hiện khi…" để chỉ hỏi một nhóm khách. Kéo thả biểu tượng ⋮⋮ rồi bấm "Lưu thứ tự" để sắp xếp.'],
            ['key' => 'scoring', 'label' => 'Cấu hình chấm điểm', 'route' => 'admin.scoring.edit', 'icon' => 'sliders',
                'help' => 'Đổi trọng số, ngưỡng và tên nhãn, số ngành gợi ý. Bấm "Chạy thử" để xem một hồ sơ bất kỳ được gợi ý ngành nào với giá trị mới (chưa lưu) so với hiện tại. Hài lòng thì bấm "Lưu". Lượt làm bài cũ không bị chấm lại. Sửa nhầm thì bấm "Quay lại bản này" ở Lịch sử thay đổi.'],
            ['key' => 'groups', 'label' => 'Mô tả 6 nhóm Holland', 'route' => 'admin.groups.edit', 'icon' => 'hexagon', 'editor' => true,
                'help' => 'Tên và đoạn giải thích 6 nhóm R, I, A, S, E, C. Chữ này hiện trên trang kết quả và email gửi khách.'],
        ],
    ],
    [
        'title' => 'Nội dung trang',
        'items' => [
            ['key' => 'intro', 'label' => 'Trang giới thiệu', 'route' => 'admin.page-editor.show', 'params' => ['page' => 'trang-gioi-thieu'], 'icon' => 'image', 'editor' => true,
                'help' => 'Trang mở ra đúng như khách thấy. Bấm vào chữ có viền chấm để gõ sửa, bấm vào ảnh để đổi ảnh, mỗi khối có nút Lên / Xuống / Ẩn. Sửa xong bấm "Lưu thay đổi" trên thanh đen. Muốn thêm khối mới hoặc thêm / bớt câu hỏi thường gặp: bấm "Thêm / bớt dòng…" trên khối, hoặc vào mục này rồi chọn tab "Các khối nội dung".'],
            ['key' => 'result-page', 'label' => 'Trang kết quả', 'route' => 'admin.page-editor.show', 'params' => ['page' => 'trang-ket-qua'], 'icon' => 'chart', 'editor' => true,
                'help' => 'Trang kết quả minh họa mở ra đúng như khách thấy (tên khách hiện là [Tên khách]). Bấm vào chữ có viền chấm để gõ sửa (lời chào, đoạn mở đầu, tên và mô tả nhóm, khối liên hệ…), rồi bấm "Lưu thay đổi". Ngành và điểm trên trang chỉ để minh họa, không sửa ở đây.'],
            ['key' => 'email', 'label' => 'Email gửi khách', 'route' => 'admin.page-editor.show', 'params' => ['page' => 'email'], 'icon' => 'mail', 'editor' => true,
                'help' => 'Email mẫu mở ra đúng như khách nhận. Bấm vào chữ có viền chấm để sửa tiêu đề, lời chào, lời mở đầu, lời kết, chữ ký, rồi bấm "Lưu thay đổi".'],
            ['key' => 'embed', 'label' => 'Nhúng vào web chính', 'route' => 'admin.content.edit', 'params' => ['page' => 'embed'], 'icon' => 'code', 'editor' => true,
                'help' => 'Nội dung khối "Trắc nghiệm Holland" hiện trên web chính, tên miền được phép nhúng và đoạn mã để gửi người làm web chính.'],
            ['key' => 'appearance', 'label' => 'Giao diện & chân trang', 'route' => 'admin.content.edit', 'params' => ['page' => 'appearance'], 'icon' => 'palette',
                'help' => 'Tên đơn vị, logo, màu, font chữ, menu đầu trang, thông tin chân trang (hotline, Zalo, địa chỉ, mạng xã hội).'],
            ['key' => 'media', 'label' => 'Thư viện ảnh', 'route' => 'admin.media.index', 'icon' => 'image', 'editor' => true,
                'help' => 'Tải ảnh lên (tự thu nhỏ, nén), xem lại ảnh đã có. Ở các trang nội dung, bấm "Chọn ảnh" để lấy ảnh từ đây.'],
        ],
    ],
    [
        'title' => 'Dữ liệu',
        'items' => [
            ['key' => 'attempts', 'label' => 'Lượt làm bài', 'route' => 'admin.attempts.index', 'icon' => 'users', 'editor' => true,
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
