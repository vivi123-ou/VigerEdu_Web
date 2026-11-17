<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Setting;
use App\Support\HtmlSanitizer;
use App\Support\IntroBlocks;
use App\Support\LinkNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Các khối nội dung trang giới thiệu: thêm, sửa, bật/tắt, kéo thả sắp xếp, xóa, xem trước.
 * Mọi thay đổi ghi nhật ký, quay lại được bản trước.
 */
class IntroBlockController extends Controller
{
    public function index(): View
    {
        return view('admin.intro-blocks.index', [
            'blocks' => IntroBlocks::all(),
            'activities' => AdminActivity::forSubject(IntroBlocks::SUBJECT, null),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->validate(['type' => ['required', Rule::in(array_keys(IntroBlocks::TYPES))]])['type'];

        $block = ['id' => IntroBlocks::newId(), 'type' => $type, 'enabled' => false, 'data' => ['title' => IntroBlocks::typeLabel($type)]];
        IntroBlocks::save([...IntroBlocks::all(), $block], 'Thêm khối "'.IntroBlocks::typeLabel($type).'"');

        return redirect()->route('admin.intro-blocks.edit', $block['id'])
            ->with('status', 'Đã thêm khối (đang tắt). Nhập nội dung, bấm "Xem trước", rồi tick "Hiện khối này" và Lưu.');
    }

    public function edit(string $id): View
    {
        return view('admin.intro-blocks.form', ['block' => $this->block($id)]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $block = $this->block($id);
        $block = [...$block, 'enabled' => $request->boolean('enabled'), 'data' => $this->validatedData($request, $block['type'])];

        IntroBlocks::save($this->replace($block), 'Sửa khối "'.($block['data']['title'] ?: IntroBlocks::typeLabel($block['type'])).'"');

        return redirect()->route('admin.intro-blocks.index')->with('status', 'Đã lưu khối. Trang mở đầu hiện ngay nội dung mới.');
    }

    /**
     * "Xem trước": trang giới thiệu với khối đang sửa (chưa lưu).
     */
    public function preview(Request $request, string $id): Response
    {
        $block = $this->block($id);
        $block = [...$block, 'enabled' => true, 'data' => $this->validatedData($request, $block['type'])];

        Setting::preview([IntroBlocks::SETTING_KEY => json_encode($this->replace($block), JSON_UNESCAPED_UNICODE)]);

        try {
            $html = view('public.home', ['isPreview' => true])->render();
        } finally {
            Setting::preview([]);
        }

        return response($html)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function toggle(string $id): RedirectResponse
    {
        $block = $this->block($id);
        $block['enabled'] = ! $block['enabled'];

        IntroBlocks::save($this->replace($block), ($block['enabled'] ? 'Bật' : 'Tắt').' khối "'.($block['data']['title'] ?? '').'"');

        return back()->with('status', ($block['enabled'] ? 'Đã bật' : 'Đã tắt').' khối.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $block = $this->block($id);

        IntroBlocks::save(array_values(array_filter(IntroBlocks::all(), fn (array $item) => $item['id'] !== $id)),
            'Xóa khối "'.($block['data']['title'] ?? IntroBlocks::typeLabel($block['type'])).'"');

        return back()->with('status', 'Đã xóa khối. Muốn lấy lại: bấm "Quay lại bản này" ở Lịch sử thay đổi.');
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

    /**
     * Kiểm tra + chuẩn hóa nội dung khối theo loại.
     *
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, string $type): array
    {
        $rules = ['title' => ['nullable', 'string', 'max:150']];

        $rules += match ($type) {
            'steps' => ['items' => ['array', 'max:8'], 'items.*.title' => ['nullable', 'string', 'max:150'], 'items.*.text' => ['nullable', 'string', 'max:500'], 'note' => ['nullable', 'string', 'max:300'], 'in_hero' => ['boolean']],
            'groups' => ['intro' => ['nullable', 'string', 'max:1500']],
            'text_image' => ['body' => ['nullable', 'string', 'max:20000'], 'image' => ['nullable', 'string', 'max:255', 'regex:#^((img|storage)/[A-Za-z0-9_\-./]+|https://\S+)$#'],
                'image_alt' => ['nullable', 'string', 'max:150'], 'image_side' => ['nullable', Rule::in(['left', 'right'])],
                'button_text' => ['nullable', 'string', 'max:60'], 'button_url' => ['nullable', 'required_with:button_text', 'url:http,https', 'max:255']],
            'faq' => ['items' => ['array', 'max:30'], 'items.*.question' => ['nullable', 'string', 'max:300'], 'items.*.answer' => ['nullable', 'string', 'max:5000']],
            'testimonials' => ['items' => ['array', 'max:20'], 'items.*.quote' => ['nullable', 'string', 'max:800'], 'items.*.name' => ['nullable', 'string', 'max:100'], 'items.*.role' => ['nullable', 'string', 'max:100']],
            default => [],
        };

        $request->merge(['button_url' => LinkNormalizer::normalize($request->input('button_url'))]);
        $data = $request->validate($rules, [
            'button_url.url' => 'Địa chỉ nút chưa đúng. Ví dụ: viger.edu.vn/du-hoc-duc',
            'button_url.required_with' => 'Có chữ trên nút thì cần nhập địa chỉ nút.',
            'image.regex' => 'Ảnh chưa đúng, vui lòng bấm "Chọn ảnh" để chọn lại.',
            '*.max' => 'Nội dung quá dài.',
        ]);

        $data['title'] = trim((string) ($data['title'] ?? ''));
        $rows = fn (string $first) => collect($data['items'] ?? [])->filter(fn ($row) => filled($row[$first] ?? null))->values();

        return match ($type) {
            'steps' => ['title' => $data['title'], 'in_hero' => $request->boolean('in_hero'), 'note' => trim((string) ($data['note'] ?? '')),
                'items' => $rows('title')->map(fn ($row) => ['title' => trim($row['title']), 'text' => trim((string) ($row['text'] ?? ''))])->all()],
            'groups' => ['title' => $data['title'], 'intro' => trim((string) ($data['intro'] ?? ''))],
            'text_image' => ['title' => $data['title'], 'body' => HtmlSanitizer::clean($data['body'] ?? ''), 'image' => $data['image'] ?? '',
                'image_alt' => trim((string) ($data['image_alt'] ?? '')), 'image_side' => $data['image_side'] ?? 'right',
                'button_text' => trim((string) ($data['button_text'] ?? '')), 'button_url' => trim((string) ($data['button_url'] ?? ''))],
            'faq' => ['title' => $data['title'],
                'items' => $rows('question')->map(fn ($row) => ['question' => trim($row['question']), 'answer' => HtmlSanitizer::clean($row['answer'] ?? '')])->all()],
            'testimonials' => ['title' => $data['title'],
                'items' => $rows('quote')->map(fn ($row) => ['quote' => trim($row['quote']), 'name' => trim((string) ($row['name'] ?? '')), 'role' => trim((string) ($row['role'] ?? ''))])->all()],
            default => ['title' => $data['title']],
        };
    }

    /**
     * @return array{id: string, type: string, enabled: bool, data: array<string, mixed>}
     */
    private function block(string $id): array
    {
        $block = IntroBlocks::find($id);
        abort_if($block === null, 404);

        return $block;
    }

    /**
     * Danh sách khối với một khối được thay.
     *
     * @param  array<string, mixed>  $block
     * @return list<array<string, mixed>>
     */
    private function replace(array $block): array
    {
        return array_map(fn (array $item) => $item['id'] === $block['id'] ? $block : $item, IntroBlocks::all());
    }
}
