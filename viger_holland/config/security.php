<?php

/*
|--------------------------------------------------------------------------
| Chống truy cập bất thường (App\Modules\System\Services\IpGuard)
|--------------------------------------------------------------------------
|
| Các con số đổi được ở "Cài đặt hệ thống" (bảng settings); ở đây là giá trị mặc định và danh sách đường dò.
| Tấn công từ hàng loạt IP (DDoS) phải chặn ở tầng trước web (Cloudflare, tường lửa của host): xem docs/SO_TAY_SU_DUNG.md mục 6.
|
*/

return [
    // Mỗi IP được gửi bấy nhiêu yêu cầu / phút cho toàn web (trang khách, API, quản trị). Vượt → báo "thử lại sau" + tính 1 lần vi phạm.
    'requests_per_minute' => 300,

    // Vi phạm bấy nhiêu lần trong 10 phút thì tự chặn IP.
    'strikes_to_block' => 10,

    // Tự chặn trong bấy nhiêu phút.
    'block_minutes' => 60,

    // Đường hacker hay dò trên web PHP (WordPress, phpMyAdmin, file cấu hình, git…). Gặp là trả 404 ngay và tính 5 lần vi phạm.
    'probe_patterns' => [
        '#^/?(wp-admin|wp-login\.php|wp-content|wp-includes|xmlrpc\.php|wordpress)#i',
        '#^/?(phpmyadmin|pma|myadmin|adminer|mysql)(/|\.php|$)#i',
        '#(^|/)\.(env|git|svn|hg|ds_store|htaccess|htpasswd|aws|ssh)(/|$|\.)#i',
        '#^/?(vendor/phpunit|cgi-bin|boaform|actuator|solr|console|_ignition|telescope|horizon)(/|$)#i',
        '#\.(bak|sql|sql\.gz|old|swp|zip|tar|tar\.gz|7z|rar)$#i',
        '#^/?(config|configuration|settings|backup|db|database|shell|eval-stdin)\.php#i',
    ],

    'probe_strikes' => 5,

    // Proxy tin cậy (Cloudflare, proxy của host): '*' hoặc danh sách IP / dải IP cách nhau dấu phẩy. Để trống khi chạy thẳng.
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    // Lệnh composer cho holland:security-audit (kiểm tra lỗ hổng package mỗi tuần).
    'composer_bin' => env('COMPOSER_BIN', 'composer'),
];
