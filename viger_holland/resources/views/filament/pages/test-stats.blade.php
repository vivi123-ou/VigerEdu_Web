{{-- Số liệu làm bài (H6): chỉ con số tổng, không tên khách. Thanh ngang có số ghi bên cạnh. --}}
@php
    $bars = function (array $rows, string $empty = 'Chưa có dữ liệu') {
        $max = max(1, ...array_map(fn ($r) => (int) $r[1], $rows ?: [[0, 0]]));
        return ['rows' => $rows, 'max' => $max, 'empty' => $empty];
    };
@endphp
<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/admin-holland.css') }}">
    <div class="hz">
        <div class="hz-tabs" role="tablist">
            @foreach ($periods as $key => $label)
                <button type="button" wire:click="setPeriod('{{ $key }}')" @class(['on' => $period === $key])>{{ $label }}</button>
            @endforeach
        </div>

        <div class="hz-stat-row">
            @foreach ($funnel as [$label, $n])
                <x-filament::section><div class="hz-stat"><small>{{ $label }}</small><b>{{ number_format($n, 0, ',', '.') }}</b></div></x-filament::section>
            @endforeach
        </div>

        <div class="hz-two">
            <x-filament::section heading="Khách đi tới bước nào" description="{{ $rate }}% người bắt đầu làm bài đã nhập đúng mã và xem báo cáo.">
                @include('filament.pages.partials.hbars', $bars($funnel))
            </x-filament::section>
            <x-filament::section heading="Khách dừng giữa bài ở đâu" description="Những lượt chưa làm xong: đã xong tới trang mấy.">
                @include('filament.pages.partials.hbars', $bars($stopped->map(fn ($n, $p) => [$p ? 'Xong trang '.$p : 'Chưa xong trang nào', $n])->values()->all(), 'Không có lượt bỏ dở'))
            </x-filament::section>
        </div>

        <x-filament::section heading="Lượt làm bài theo ngày" description="Cột xanh đậm: bắt đầu làm bài. Cột cam: nhập đúng mã, xem báo cáo.">
            @php($dayMax = max(1, ...array_map(fn ($d) => $d[1], $byDay)))
            <div class="hz-days" role="img" aria-label="Lượt làm bài theo ngày">
                @foreach ($byDay as [$day, $start, $done])
                    <div class="hz-day" title="{{ $day }}: {{ $start }} bắt đầu, {{ $done }} xem báo cáo">
                        <div class="hz-day__bars"><i style="height: {{ round($start / $dayMax * 100) }}%"></i><i class="b" style="height: {{ round($done / $dayMax * 100) }}%"></i></div>
                        <small>{{ $day }}</small>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <div class="hz-two">
            <x-filament::section heading="Cặp mã phổ biến" description="2 chữ đầu của những lượt đã làm xong.">
                @include('filament.pages.partials.hbars', $bars($codes))
            </x-filament::section>
            <x-filament::section heading="Khách đến từ đâu" description="Theo nguồn quảng cáo (utm_source); không có thì là Trực tiếp.">
                @include('filament.pages.partials.hbars', $bars($sources))
            </x-filament::section>
            <x-filament::section heading="Ngành được gợi ý nhiều" description="Ngành khách thấy trong báo cáo. Nên ưu tiên kiểm tra nguồn chính thức cho các ngành này.">
                @include('filament.pages.partials.hbars', $bars($majors))
            </x-filament::section>
            <x-filament::section heading="Chất lượng bài làm" description="Theo dấu hiệu làm vội, chọn một kiểu.">
                @include('filament.pages.partials.hbars', $bars($quality))
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
