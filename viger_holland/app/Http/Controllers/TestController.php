<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Làm gì:   Trang làm bài của khách (/lam-bai). Trang thật làm ở H3 theo mẫu đã chốt.
 *           Trong lúc chờ: trên máy chạy thử (APP_ENV=local) hiện bản mẫu luồng khách docs/mau_giao_dien/luong-khach.html
 *           (kèm trang con trang-gioi-thieu.html nhúng ở màn hình 0) để xem giao diện; máy chủ thật hiện trang "đang cập nhật".
 * Ai gọi:   route test.show; nút "Xem trang làm bài như khách" trong trang quản trị.
 * Dữ liệu:  không có.
 */
class TestController extends Controller
{
    /** Các file mẫu được phép mở (?mau=...). Chỉ đúng các file này, không mở được file nào khác. */
    public const MOCKUPS = [
        'luong-khach' => 'docs/mau_giao_dien/luong-khach.html',
        'trang-gioi-thieu' => 'docs/mau_giao_dien/trang-gioi-thieu.html',
    ];

    public function show(Request $request): View|Response
    {
        $path = self::MOCKUPS[(string) $request->query('mau', 'luong-khach')] ?? null;

        if (app()->isLocal() && $path !== null && is_file(base_path($path))) {
            $html = strtr((string) file_get_contents(base_path($path)), [
                '../../public/' => '/',
                'src="trang-gioi-thieu.html"' => 'src="'.e(route('test.show', ['mau' => 'trang-gioi-thieu'])).'"',
            ]);

            return response($html)->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return view('public.updating');
    }
}
