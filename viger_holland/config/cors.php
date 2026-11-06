<?php

/*
| CORS mặc định của Laravel tắt (paths rỗng): API công khai /api/v1/public/intro tự gắn
| Access-Control-Allow-Origin theo danh sách tên miền admin nhập ở trang "Nhúng vào web chính"
| (App\Http\Controllers\EmbedController). Không cho mọi tên miền gọi.
*/

return [
    'paths' => [],
    'allowed_methods' => ['GET'],
    'allowed_origins' => [],
    'allowed_origins_patterns' => [],
    'allowed_headers' => [],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
