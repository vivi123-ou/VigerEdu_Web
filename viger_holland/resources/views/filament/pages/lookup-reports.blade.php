{{-- Tra cứu báo cáo khách (H4b), theo mẫu admin-filament.html màn hình 2. Chỉ khớp đúng, không có danh sách. --}}
<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/admin-holland.css') }}">
    <div class="hz">
        <x-filament::section>
            <form wire:submit="find" class="hz-row">
                <div class="hz-grow">
                    <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                        <x-filament::input type="text" wire:model="search" autocomplete="off" autofocus
                            placeholder="Số điện thoại, email hoặc mã báo cáo" aria-label="Số điện thoại, email hoặc mã báo cáo" />
                    </x-filament::input.wrapper>
                </div>
                <x-filament::button type="submit" size="lg" wire:loading.attr="disabled">Tìm</x-filament::button>
            </form>
        </x-filament::section>

        @if ($message)
            <p class="hz-msg" role="status">{{ $message }}</p>
        @endif

        @if ($rows)
            <x-filament::section :heading="count($rows) > 1 ? 'Khách đã làm bài '.count($rows).' lần, lần mới nhất ở trên' : null">
                @foreach ($rows as $row)
                    <div class="hz-result">
                        <span class="hz-code">{{ $row['code3'] }}</span>
                        <div>
                            <b>{{ $row['name'] }}</b>
                            @if ($row['role'])
                                <span class="hz-badge">{{ $row['role'] }}</span>
                            @endif
                            <dl class="hz-dl">
                                <dt>Năm sinh</dt><dt>Làm bài lúc</dt><dt>Mã báo cáo</dt><dt>Bài làm</dt>
                                <dd>{{ $row['birth'] ?? '—' }}</dd>
                                <dd>{{ $row['at'] ?? '—' }}</dd>
                                <dd>{{ $row['reportCode'] }}</dd>
                                <dd>
                                    <span @class(['hz-badge', 'ok' => $row['reliability'] === 'Tin cậy', 'warn' => $row['reliability'] === 'Cần xem lại', 'bad' => $row['reliability'] === 'Chưa đủ tin cậy'])>
                                        {{ $row['reliability'] === 'Tin cậy' ? 'Làm bài cẩn thận' : $row['reliability'] }}
                                    </span>
                                </dd>
                            </dl>
                        </div>
                        <x-filament::button tag="a" :href="$this->reportUrl($row['uuid'])" icon="heroicon-o-arrow-right" icon-position="after">
                            Đọc báo cáo đầy đủ
                        </x-filament::button>
                    </div>
                @endforeach
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
