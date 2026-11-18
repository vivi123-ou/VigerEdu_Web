{{-- Đoạn mã gửi người làm web chính: chỉ dùng một lần lúc lắp đặt, nên thu gọn và chỉ quản trị viên thấy. --}}
@if (auth()->user()?->isAdmin())
    <details class="card embed-tech">
        <summary class="card__title" style="cursor:pointer">Dành cho người làm web chính (chỉ dùng một lần lúc lắp đặt) ▸</summary>
        <p class="muted">Lần đầu, chép một trong 3 đoạn dưới đây gửi người làm web chính để gắn ô vào trang. Sau đó không cần gửi lại:
            sửa nội dung ở trên rồi bấm Lưu là web chính tự đổi (chậm nhất 5 phút).</p>
        <ol class="embed-codes">
            <li>
                <strong>Gắn ô có sẵn</strong> (dễ nhất): dán vào chỗ muốn hiện ô.
                @include('admin.partials.copy-code', ['code' => '<div id="holland"></div>'."\n".'<script src="'.route('embed.script').'" data-target="#holland" defer></script>'])
            </li>
            <li>
                <strong>Tự làm giao diện</strong>: web chính đọc nội dung ô (tiêu đề, mô tả, ảnh, chữ nút) từ địa chỉ:
                @include('admin.partials.copy-code', ['code' => route('api.public.intro')])
            </li>
            <li>
                <strong>Chỉ đặt link</strong> trên menu hoặc nút:
                @include('admin.partials.copy-code', ['code' => route('home', ['utm_source' => 'web-chinh'])])
            </li>
        </ol>
        <p class="muted"><a href="{{ route('embed') }}" target="_blank" rel="noopener">Xem ô đang hiện ↗</a></p>
    </details>
@endif
