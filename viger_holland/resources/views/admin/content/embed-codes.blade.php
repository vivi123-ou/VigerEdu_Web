{{-- Đoạn mã gửi người làm web chính (Tuấn). --}}
<section class="card">
    <h2 class="card__title">Gửi người làm web chính (chọn 1 trong 3 cách)</h2>
    <ol class="embed-codes">
        <li>
            <strong>Nhúng khối có sẵn</strong> (dễ nhất): dán vào chỗ muốn hiện khối.
            @include('admin.partials.copy-code', ['code' => '<div id="holland"></div>'."\n".'<script src="'.route('embed.script').'" data-target="#holland" defer></script>'])
        </li>
        <li>
            <strong>Tự làm giao diện</strong>: web chính đọc nội dung khối (tiêu đề, mô tả, ảnh, chữ nút, số lượt làm bài) từ địa chỉ:
            @include('admin.partials.copy-code', ['code' => route('api.public.intro')])
        </li>
        <li>
            <strong>Chỉ đặt link</strong> trên menu hoặc nút:
            @include('admin.partials.copy-code', ['code' => route('home', ['utm_source' => 'web-chinh'])])
        </li>
    </ol>
    <p class="muted">Đổi nội dung ở trên rồi Lưu: web chính tự hiện nội dung mới (chậm nhất 5 phút), không phải sửa gì bên web chính.
        <a href="{{ route('embed') }}" target="_blank" rel="noopener">Xem khối đang hiện ↗</a></p>
</section>
