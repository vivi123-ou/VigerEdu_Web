{{-- Thanh công cụ của một khối khi "Sửa trực tiếp trên trang" (chỉ admin thấy). --}}
<div class="ed-bar" data-ed-block="{{ $block['id'] }}">
    <span class="ed-bar__name">Khối: {{ \App\Support\IntroBlocks::typeLabel($block['type']) }}@unless ($block['enabled']) · <strong>đang ẩn</strong>@endunless</span>
    <span class="ed-bar__actions">
        <button type="button" data-ed-move="up" @disabled($first) title="Đưa khối lên trên">↑ Lên</button>
        <button type="button" data-ed-move="down" @disabled($last) title="Đưa khối xuống dưới">↓ Xuống</button>
        <button type="button" data-ed-toggle="{{ route('admin.intro-blocks.toggle', $block['id']) }}">{{ $block['enabled'] ? 'Ẩn khối' : 'Hiện khối' }}</button>
        <a href="{{ route('admin.intro-blocks.edit', $block['id']) }}">Thêm / bớt dòng…</a>
    </span>
</div>
