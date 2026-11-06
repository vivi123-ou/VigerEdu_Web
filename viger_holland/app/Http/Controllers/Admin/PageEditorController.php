<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestResultMail;
use App\Services\PagePreview;
use App\Services\ResultPresenter;
use App\Support\PageEditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * "Sửa trực tiếp trên trang": mở trang khách thấy (trang giới thiệu, trang kết quả mẫu, email) ở chế độ sửa –
 * bấm vào chữ để gõ, bấm vào ảnh để đổi, bấm "Lưu". Mọi thay đổi ghi lịch sử như các trang nội dung khác.
 */
class PageEditorController extends Controller
{
    /** Trang sửa được => [tên, mục menu (quyền), trang biểu mẫu tương ứng]. */
    public const PAGES = [
        'trang-gioi-thieu' => ['Trang giới thiệu', 'intro', 'admin.content.edit:intro'],
        'trang-ket-qua' => ['Trang kết quả', 'result-page', 'admin.content.edit:result-page'],
        'email' => ['Email gửi khách', 'email', 'admin.content.edit:email'],
    ];

    public function show(Request $request, string $page, PagePreview $preview, ResultPresenter $presenter): Response
    {
        [$title, $menuKey, $formRoute] = self::PAGES[$page] ?? abort(404);
        abort_unless($request->user()->canAccessMenu($menuKey), 403, 'Tài khoản của bạn không có quyền vào mục này.');

        [$formName, $formPage] = explode(':', $formRoute);
        PageEditor::start();

        try {
            $html = match ($page) {
                'trang-ket-qua' => view('public.result', ['attempt' => $attempt = $preview->sampleAttempt(), 'result' => $presenter->present($attempt)])->render(),
                'email' => (new TestResultMail($preview->sampleAttempt()))->render(),
                default => view('public.home')->render(),
            };

            $toolbar = view('admin.page-editor.toolbar', [
                'pageKey' => $page,
                'title' => $title,
                'formUrl' => route($formName, $formPage),
                'viewUrl' => match ($page) {
                    'trang-gioi-thieu' => route('home'), default => null
                },
                'isEmail' => $page === 'email',
            ])->render();
        } finally {
            // Chế độ sửa chỉ dùng cho lần dựng trang này.
            PageEditor::stop();
        }

        $assets = '<link rel="stylesheet" href="'.e(asset('css/page-editor.css')).'"><meta name="csrf-token" content="'.e(csrf_token()).'">';
        $footer = view('admin.media.picker')->render().'<script src="'.e(asset('js/page-editor.js')).'" defer></script>';
        $html = preg_replace_callback('#</head>#i', fn () => $assets.'</head>', $html, 1);
        $html = preg_replace_callback('#<body([^>]*)>#i', fn (array $match) => '<body'.$match[1].' data-page-editor>'.$toolbar, $html, 1);
        $html = preg_replace_callback('#</body>#i', fn () => $footer.'</body>', $html, 1);

        return response($html)->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'no-store');
    }

    public function save(Request $request): JsonResponse
    {
        $changes = $request->validate(['changes' => ['required', 'array', 'max:200']])['changes'];

        $count = PageEditor::save($request->user(), $changes);

        return response()->json([
            'saved' => $count,
            'message' => $count > 0 ? "Đã lưu {$count} thay đổi. Khách thấy ngay nội dung mới." : 'Không có thay đổi nào.',
        ]);
    }
}
