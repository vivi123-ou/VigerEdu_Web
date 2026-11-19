<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\IntroBlocks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Làm gì:   Hai lệnh nút trên khối khi "Sửa trực tiếp trên trang": ẩn / hiện một khối, đổi thứ tự các khối.
 *           Thêm, sửa, xóa khối làm ở trang Filament "Trang mở đầu" (App\Modules\Content\Filament\Pages\IntroPage).
 * Ai gọi:   public/js/page-editor.js (thanh công cụ của khối, resources/views/public/blocks/edit-bar.blade.php).
 * Dữ liệu:  settings.intro_blocks qua App\Support\IntroBlocks (ghi lịch sử, quay lại được).
 */
class IntroBlockController extends Controller
{
    public function toggle(string $id): RedirectResponse
    {
        $block = IntroBlocks::find($id);
        abort_if($block === null, 404);
        $block['enabled'] = ! $block['enabled'];

        IntroBlocks::save(
            array_map(fn (array $item) => $item['id'] === $id ? $block : $item, IntroBlocks::all()),
            ($block['enabled'] ? 'Bật' : 'Tắt').' khối "'.($block['data']['title'] ?? '').'"',
        );

        return back()->with('status', ($block['enabled'] ? 'Đã bật' : 'Đã tắt').' khối.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $order = $request->validate(['order' => ['required', 'array'], 'order.*' => ['string']])['order'];
        $blocks = collect(IntroBlocks::all())->keyBy('id');

        $sorted = collect($order)->filter(fn (string $id) => $blocks->has($id))->map(fn (string $id) => $blocks[$id])
            ->merge($blocks->except($order)->values())
            ->values()->all();

        IntroBlocks::save($sorted, 'Sắp xếp lại các khối trang giới thiệu');

        return back()->with('status', 'Đã lưu thứ tự mới.');
    }
}
