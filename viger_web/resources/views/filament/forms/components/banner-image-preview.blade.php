{{-- Xem trước ảnh banner khi dùng đường dẫn ngoài --}}
<div x-data="{ failed: false }">
    <img
        src="{{ $get('image_url') }}"
        alt="Xem trước ảnh banner"
        x-show="! failed"
        x-on:error="failed = true"
        style="max-height: 220px; width: auto; max-width: 100%; border-radius: 0.5rem; object-fit: cover;"
    />
    <p x-show="failed" x-cloak style="color: #dc2626; font-size: 0.875rem;">
        Không tải được ảnh từ đường dẫn này. Kiểm tra lại link (phải là link trực tiếp tới file ảnh).
    </p>
</div>
