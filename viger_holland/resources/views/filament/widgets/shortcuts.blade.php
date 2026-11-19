<x-filament-widgets::widget>
    <style>
        .hl-hello{display:flex;align-items:center;gap:14px;margin-bottom:16px}
        .hl-hello p{margin:0}.hl-hello small{color:#6b7280}
        .hl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px}
        .hl-tile{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:12px;background:#fff;box-shadow:0 0 0 1px rgba(17,24,39,.07);transition:box-shadow .15s}
        .hl-tile:hover{box-shadow:0 0 0 1px rgba(17,24,39,.07),0 6px 18px -8px rgba(17,24,39,.25)}
        .hl-tile .hl-ic{flex:none;width:38px;height:38px;border-radius:10px;display:grid;place-items:center;background:#fdf2f2;color:#c1121f}
        .hl-tile b{display:block;font-weight:600;color:#111827}.hl-tile span.hl-t{color:#6b7280;font-size:13px}
        .hl-soon{display:inline-block;margin-left:6px;font-size:11px;font-weight:600;color:#6b7280;background:#f3f4f6;border-radius:6px;padding:0 6px;vertical-align:1px}
        .dark .hl-tile{background:rgb(24 24 27)}.dark .hl-tile b{color:#f9fafb}
    </style>

    <div class="hl-hello">
        <p><strong>Xin chào {{ $name }}.</strong> Hôm nay bạn muốn làm gì? Bấm một ô bên dưới.<br>
            <small>Không biết một trang dùng để làm gì: bấm nút <strong>?</strong> màu vàng ở góc trên trang đó.</small></p>
    </div>

    <div class="hl-grid">
        @foreach ($shortcuts as $item)
            <a class="hl-tile" href="{{ $item['url'] }}">
                <span class="hl-ic"><x-filament::icon :icon="$item['icon']" style="width:22px;height:22px" /></span>
                <span>
                    <b>{{ $item['label'] }}@if (filled($item['stage'] ?? null))<span class="hl-soon">Sắp có</span>@endif</b>
                    <span class="hl-t">{{ $item['summary'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</x-filament-widgets::widget>
