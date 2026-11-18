<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PagePreview;
use App\Support\ContentPreview;
use Illuminate\Http\Response;

/**
 * Làm gì:   Mở trang khách với nội dung đang gõ (chưa lưu) sau khi bấm "Xem trước" ở trang quản trị.
 * Ai gọi:   Route admin.content.preview (thẻ mới do nút "Xem trước" mở).
 * Dữ liệu:  ContentPreview (cache), không ghi database.
 */
class ContentPreviewController extends Controller
{
    public function __invoke(string $token, PagePreview $preview): Response
    {
        $data = ContentPreview::get($token);
        abort_if($data === null, 404);

        return response($preview->render($data['target'], $data['values']))
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store');
    }
}
