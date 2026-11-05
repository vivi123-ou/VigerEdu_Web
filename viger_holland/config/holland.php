<?php

/*
|--------------------------------------------------------------------------
| Cấu hình web Holland
|--------------------------------------------------------------------------
|
| Chỉ chứa giá trị kỹ thuật đọc từ .env. Mọi chữ, hình, lựa chọn mà khách
| nhìn thấy nằm trong bảng `settings` / nội dung admin (nguyên tắc "mở").
| Giá trị trong bảng `settings` được ưu tiên hơn giá trị ở đây.
|
*/

return [

    // Thuật toán chấm điểm (giai đoạn 2: App\Engines\RiasecFitEngineV2)
    'engine' => env('HOLLAND_ENGINE', 'riasec_fit_v2'),

    'crm' => [
        'enabled' => (bool) env('HOLLAND_CRM_ENABLED', false),
        'url' => env('HOLLAND_CRM_URL'),
        'token' => env('HOLLAND_CRM_TOKEN'),
        'timeout' => 5,
    ],

    // Xóa SĐT/email trong lượt làm bài sau khi CRM xác nhận đã nhận
    'purge_contact' => (bool) env('HOLLAND_PURGE_CONTACT', false),

    'chat_widget_url' => env('HOLLAND_CHAT_WIDGET_URL'),

    // Tài khoản quản trị đầu tiên do AdminSeeder tạo (không ghi đè nếu đã có)
    'admin' => [
        'username' => env('HOLLAND_ADMIN_USERNAME', 'admin'),
        'password' => env('HOLLAND_ADMIN_PASSWORD'),
        'name' => env('HOLLAND_ADMIN_NAME', 'Quản trị viên'),
        'email' => env('HOLLAND_ADMIN_EMAIL'),
    ],

    // Vai trò tài khoản admin. EDITOR (marketing) sẽ thêm ở giai đoạn 3.
    'roles' => [
        'ADMIN' => 'Quản trị viên',
    ],

    // Thư mục dữ liệu mẫu dùng cho seeder
    'seed_path' => database_path('seeders/data/holland'),

];
