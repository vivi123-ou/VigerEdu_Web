{{-- Hộp "Chọn ảnh": chọn từ thư viện hoặc tải ảnh mới. JS trong public/js/admin.js (data-media-dialog). --}}
<dialog class="media-dialog" id="media-dialog" data-media-dialog
        data-list-url="{{ route('admin.media.index') }}" data-upload-url="{{ route('admin.media.store') }}">
    <div class="media-dialog__head">
        <h2>Chọn ảnh</h2>
        <form method="dialog"><button class="btn btn--small btn--plain" type="submit" aria-label="Đóng">Đóng</button></form>
    </div>
    <label class="btn btn--small media-dialog__upload">
        Tải ảnh mới lên
        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-media-upload hidden>
    </label>
    <p class="muted media-dialog__status" data-media-status></p>
    <ul class="media-grid media-grid--pick" data-media-list></ul>
    <button class="btn btn--small" type="button" data-media-more hidden>Xem thêm ảnh</button>
</dialog>
