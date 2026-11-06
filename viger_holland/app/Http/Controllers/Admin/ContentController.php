<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Setting;
use App\Services\PagePreview;
use App\Support\ContentPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Các trang "Nội dung trang" (config/admin_content.php): sửa chữ, ảnh, màu…, "Xem trước" trước khi lưu,
 * lịch sử thay đổi và quay lại bản cũ.
 */
class ContentController extends Controller
{
    public function edit(string $page): View
    {
        $config = $this->page($page);

        return view('admin.content.edit', [
            'pageKey' => $page,
            'page' => $config,
            'values' => ContentPages::currentValues($config),
            'activities' => AdminActivity::forSubject($this->subject($page), null),
        ]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        $config = $this->page($page);
        $values = ContentPages::validate($config, $request->all());
        $current = ContentPages::currentValues($config);

        $before = [];
        $after = [];
        foreach ($values as $key => $value) {
            if ((string) ($current[$key] ?? '') !== $value) {
                $before[$key] = $current[$key] ?? null;
                $after[$key] = $value;
                Setting::put($key, $value, $config['group']);
            }
        }

        if ($after === []) {
            return back()->with('status', 'Không có thay đổi nào.');
        }

        Cache::forget('holland.public_intro');
        AdminActivity::record(AdminActivity::ACTION_UPDATE, $this->subject($page), null,
            'Sửa "'.$config['title'].'" ('.count($after).' ô)', $before, $after);

        return back()->with('status', 'Đã lưu. Trang khách hiện ngay nội dung mới.');
    }

    /**
     * "Xem trước": trang khách với nội dung đang gõ, chưa lưu.
     */
    public function preview(Request $request, string $page, PagePreview $preview): Response
    {
        $config = $this->page($page);
        $values = ContentPages::validate($config, $request->all());

        return response($preview->render($config['preview'], $values))
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * @return array<string, mixed>
     */
    private function page(string $page): array
    {
        $config = ContentPages::find($page);
        abort_if($config === null, 404);
        abort_unless(auth()->user()?->canAccessMenu($config['menu']), 403, 'Tài khoản của bạn không có quyền vào mục này.');

        return $config;
    }

    private function subject(string $page): string
    {
        return AdminActivity::SUBJECT_SETTINGS.'.'.$page;
    }
}
