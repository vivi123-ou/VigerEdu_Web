<?php

use App\Engines\RiasecFitEngineV2;

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

    // Thuật toán chấm điểm đang dùng (mã trong danh sách 'engines' bên dưới)
    'engine' => env('HOLLAND_ENGINE', 'riasec_fit_v2'),

    // Danh sách engine cài sẵn: mã => class (cách thêm engine mới: docs/THAY_THUAT_TOAN.md)
    'engines' => [
        'riasec_fit_v2' => RiasecFitEngineV2::class,
    ],

    // Tham số chấm điểm mặc định = bản chốt (khớp holland_config.json).
    // Giai đoạn 3: admin sửa trên trang "Cấu hình chấm điểm" (lưu trong settings, ưu tiên hơn ở đây).
    'scoring' => [
        'weight_code_match' => 0.6,
        'weight_score_match' => 0.4,
        'labels' => [
            ['min' => 0.80, 'label' => 'Rất phù hợp'],
            ['min' => 0.65, 'label' => 'Phù hợp'],
            ['min' => 0.50, 'label' => 'Có thể cân nhắc'],
            ['min' => 0.0, 'label' => 'Ít phù hợp'],
        ],
        'top_university' => 5,
        'top_vocational' => 3,
        'di3_threshold' => 0.15,
        // Chữ trong câu lý do: "AIS ↔ IAS · bạn đạt I 67%…"
        'reason_subject' => 'bạn',
    ],

    // Danh mục ngành dự phòng khi chưa có / không gọi được CRM
    'majors_json' => database_path('seeders/data/holland/majors.json'),

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

    // Tài khoản marketing mẫu (không bắt buộc), do AdminSeeder tạo để thử nghiệm thu
    'editor' => [
        'username' => env('HOLLAND_EDITOR_USERNAME'),
        'password' => env('HOLLAND_EDITOR_PASSWORD'),
        'name' => env('HOLLAND_EDITOR_NAME', 'Marketing'),
    ],

    // Vai trò tài khoản admin. EDITOR (marketing) chỉ thấy các mục có 'editor' => true trong config/admin_menu.php.
    'roles' => [
        'ADMIN' => 'Quản trị viên',
        'EDITOR' => 'Marketing (nội dung trang)',
    ],

    // Danh sách font admin được chọn (Google Fonts, đều hỗ trợ tiếng Việt).
    // Tên font => [kiểu dự phòng, các độ đậm cần tải]
    'fonts' => [
        'Work Sans' => ['sans-serif', '400;500;600;700'],
        'Playfair Display' => ['serif', '400;600;700'],
        'Be Vietnam Pro' => ['sans-serif', '400;500;600;700'],
        'Roboto' => ['sans-serif', '400;500;700'],
        'Montserrat' => ['sans-serif', '400;500;600;700'],
        'Lora' => ['serif', '400;600;700'],
    ],

    // Thư mục dữ liệu mẫu dùng cho seeder
    'seed_path' => database_path('seeders/data/holland'),

];
