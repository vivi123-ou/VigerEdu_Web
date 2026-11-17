<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Làm gì:   Trang làm bài của khách (/lam-bai). Trang thật làm ở H3 theo mẫu đã chốt.
 *           Trong lúc chờ: trên máy chạy thử (APP_ENV=local) hiện bản mẫu luồng khách docs/mau_giao_dien/luong-khach.html
 *           để xem giao diện; máy chủ thật hiện trang "đang cập nhật".
 * Ai gọi:   route test.show; nút "Xem trang làm bài như khách" trong trang quản trị.
 * Dữ liệu:  không có.
 */
class TestController extends Controller
{
    public const MOCKUP = 'docs/mau_giao_dien/luong-khach.html';

    public function show(): View|Response
    {
        if (app()->isLocal() && is_file(base_path(self::MOCKUP))) {
            $html = str_replace('../../public/', '/', (string) file_get_contents(base_path(self::MOCKUP)));

            return response($html)->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return view('public.updating');
    }
}
