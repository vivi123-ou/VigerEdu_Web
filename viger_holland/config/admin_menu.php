<?php

/*
|--------------------------------------------------------------------------
| Các trang nội dung làm từ trước, chưa chuyển sang Filament
|--------------------------------------------------------------------------
|
| Khu quản trị chính do Filament dựng (app/Providers/Filament/AdminPanelProvider.php); các mục dưới đây
| hiện trong menu Filament (nhóm "Nội dung trang") và mở trang cũ. Sẽ chuyển dần sang Filament ở H6.
| 'permission' = mã quyền cần có (app/Modules/Access/Permissions.php). 'help' = nội dung nút "?" của trang.
|
*/

return [
    [
        'title' => 'Nội dung trang',
        'items' => [
            ['key' => 'intro', 'permission' => 'content.manage', 'label' => 'Trang giới thiệu', 'route' => 'admin.page-editor.show', 'params' => ['page' => 'trang-gioi-thieu'], 'icon' => 'image',
                'help' => 'Trang mở ra đúng như khách thấy. Bấm vào chữ có viền chấm để gõ sửa, bấm vào ảnh để đổi ảnh, mỗi khối có nút Lên / Xuống / Ẩn. Sửa xong bấm "Lưu thay đổi" trên thanh đen. Muốn thêm khối mới hoặc thêm / bớt câu hỏi thường gặp: bấm "Thêm / bớt dòng…" trên khối, hoặc vào mục này rồi chọn tab "Các khối nội dung".'],
            ['key' => 'embed', 'permission' => 'content.manage', 'label' => 'Nhúng vào web chính', 'route' => 'admin.content.edit', 'params' => ['page' => 'embed'], 'icon' => 'code',
                'help' => 'Nội dung khối "Trắc nghiệm Holland" hiện trên web chính, tên miền được phép nhúng và đoạn mã để gửi người làm web chính.'],
            ['key' => 'appearance', 'permission' => 'appearance.manage', 'label' => 'Giao diện & chân trang', 'route' => 'admin.content.edit', 'params' => ['page' => 'appearance'], 'icon' => 'palette',
                'help' => 'Tên đơn vị, logo, màu, font chữ, menu đầu trang, thông tin chân trang (hotline, Zalo, địa chỉ, mạng xã hội).'],
            ['key' => 'media', 'permission' => 'content.manage', 'label' => 'Thư viện ảnh', 'route' => 'admin.media.index', 'icon' => 'image',
                'help' => 'Tải ảnh lên (tự thu nhỏ, nén), xem lại ảnh đã có. Ở các trang nội dung, bấm "Chọn ảnh" để lấy ảnh từ đây.'],
        ],
    ],
];
