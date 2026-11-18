{{-- Đoạn mã gửi người làm web chính (trang Ô giới thiệu trên viger.edu.vn, chỉ quản trị viên). --}}
@php
    $codes = [
        ['Gắn ô có sẵn (dễ nhất): dán vào chỗ muốn hiện ô', '<div id="holland"></div>'."\n".'<script src="'.route('embed.script').'" data-target="#holland" defer></script>'],
        ['Tự làm giao diện: web chính đọc nội dung ô (tiêu đề, mô tả, ảnh, chữ nút) từ địa chỉ', route('api.public.intro')],
        ['Chỉ đặt link trên menu hoặc nút', route('home', ['utm_source' => 'viger.edu.vn'])],
    ];
@endphp
<ol class="hz-codes">
    @foreach ($codes as [$label, $code])
        <li x-data="{ copied: false }">
            <p><strong>{{ $loop->iteration }}.</strong> {{ $label }}</p>
            <div class="hz-codes__row">
                <pre class="hz-codes__code" x-ref="code">{{ $code }}</pre>
                <x-filament::button size="sm" color="gray" icon="heroicon-o-clipboard-document"
                    x-on:click="navigator.clipboard.writeText($refs.code.textContent); copied = true; setTimeout(() => copied = false, 2000)">
                    <span x-text="copied ? 'Đã chép' : 'Chép'">Chép</span>
                </x-filament::button>
            </div>
        </li>
    @endforeach
</ol>
<p class="hz-codes__more"><a href="{{ route('embed') }}" target="_blank" rel="noopener">Xem ô đang hiện ↗</a></p>
<style>
    .hz-codes{display:grid;gap:1rem;margin:0;padding:0;list-style:none}
    .hz-codes p{margin:0 0 .35rem;font-size:.875rem}
    .hz-codes__row{display:flex;gap:.5rem;align-items:flex-start}
    .hz-codes__code{flex:1;min-width:0;margin:0;padding:.5rem .75rem;border-radius:.5rem;background:rgba(127,127,127,.1);font-size:.8rem;white-space:pre-wrap;word-break:break-all}
    .hz-codes__more{margin-top:1rem;font-size:.875rem}
    .hz-codes__more a{color:var(--primary-600);font-weight:500}
</style>
