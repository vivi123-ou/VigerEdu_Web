{{-- Nội dung nút "?": lời giới thiệu, các bước "muốn làm gì → làm thế nào", lưu ý. Dùng chung cho trang Filament và trang cũ. --}}
@php($help = $help ?? [])
<div style="display:grid;gap:12px;font-size:14px;line-height:1.6">
    @if (filled($help['intro'] ?? null))
        <p style="margin:0">{{ $help['intro'] }}</p>
    @endif

    @if (! empty($help['steps']))
        <ol style="margin:0;padding:0;list-style:none;display:grid;gap:8px">
            @foreach ($help['steps'] as $i => [$want, $how])
                <li style="display:flex;gap:10px">
                    <span style="flex:none;width:22px;height:22px;border-radius:50%;background:#fdf2f2;color:#c1121f;font-weight:700;font-size:12px;line-height:22px;text-align:center">{{ $i + 1 }}</span>
                    <span><strong>{{ $want }}:</strong> {{ $how }}</span>
                </li>
            @endforeach
        </ol>
    @endif

    @if (filled($help['note'] ?? null))
        <p style="margin:0;color:#6b7280;font-size:13px">{{ $help['note'] }}</p>
    @endif
</div>
