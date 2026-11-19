{{-- Thanh công cụ "Sửa trực tiếp trên trang" (cố định trên cùng). --}}
<div class="pe-bar" data-pe-bar data-save-url="{{ route('admin.page-editor.save') }}"
     data-reorder-url="{{ route('admin.intro-blocks.reorder') }}">
    <div class="pe-bar__main">
        <strong class="pe-bar__title">Đang sửa: {{ $title }}</strong>
        <span class="pe-bar__hint">Bấm vào chữ có viền chấm để gõ sửa, bấm vào ảnh để đổi ảnh, xong bấm "Lưu thay đổi".</span>
        <span class="pe-bar__status" data-pe-status role="status"></span>
    </div>
    <div class="pe-bar__actions">
        <button type="button" class="pe-btn pe-btn--primary" data-pe-save disabled>Lưu thay đổi</button>
        <button type="button" class="pe-btn" data-pe-reset disabled>Bỏ thay đổi</button>
        @if ($viewUrl)
            <a class="pe-btn" href="{{ $viewUrl }}" target="_blank" rel="noopener">Xem như khách ↗</a>
        @endif
        <a class="pe-btn pe-btn--plain" href="{{ $formUrl }}">Sửa dạng biểu mẫu</a>
        <a class="pe-btn pe-btn--plain" href="{{ \App\Support\AdminMenu::homeUrl() }}">← Về trang quản trị</a>
    </div>
    @if ($isEmail)
        <div class="pe-bar__email">Tiêu đề email: {{ ed('email_subject', null, 'Tiêu đề email') }}</div>
    @endif
    <div class="pe-format" data-pe-format hidden>
        <button type="button" data-cmd="bold" title="Chữ đậm"><strong>B</strong></button>
        <button type="button" data-cmd="italic" title="Chữ nghiêng"><em>I</em></button>
        <button type="button" data-cmd="link" title="Gắn link">Link</button>
        <button type="button" data-cmd="insertUnorderedList" title="Danh sách chấm">• Danh sách</button>
        <button type="button" data-cmd="unlink" title="Bỏ link">Bỏ link</button>
    </div>
</div>
