{{--
    Khách cần liên lạc (H4b), theo mẫu admin-filament.html màn hình 4. Không có kết quả bài làm, không có nút tải.
    Không ghi ra số ngày được xem (Vân chốt 17/11).
--}}
@php($P = \App\Modules\Respondent\Filament\Pages\ContactsToCall::class)
<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/admin-holland.css') }}">
    <div class="hz">
        <div class="hz-tabs" role="tablist">
            @foreach (['hom-nay' => 'Hôm nay', 'hom-qua' => 'Hôm qua', 'tat-ca' => 'Tất cả'] as $key => $label)
                <button type="button" role="tab" wire:click="setTab('{{ $key }}')" @class(['on' => $tab === $key]) aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                    {{ $label }}<span class="cnt">{{ $counts[$key] }}</span>
                </button>
            @endforeach
        </div>

        <x-filament::section>
            <div class="hz-filters">
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Tìm theo tên, số điện thoại…" aria-label="Tìm theo tên, số điện thoại">
                <select wire:model.live="role" aria-label="Đối tượng">
                    <option value="">Đối tượng: Tất cả</option>
                    @foreach ($roles as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <select wire:model.live="province" aria-label="Tỉnh / thành">
                    <option value="">Tỉnh: Tất cả</option>
                    @foreach ($provinces as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="hz-scroll" style="margin-top:14px">
                <table class="hz-table">
                    <thead><tr><th>Họ tên</th><th>Điện thoại phụ huynh</th><th>Điện thoại học sinh</th><th>Email</th><th>Đối tượng</th><th>Năm sinh</th><th>Tỉnh / thành</th><th>Để lại lúc</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($rows as $c)
                            <tr>
                                <td><b>{{ $c['name'] }}</b></td>
                                <td>@if ($c['parentPhone'])<a class="hz-link" href="tel:{{ $c['parentPhone'] }}">{{ $c['parentPhone'] }}</a>@else — @endif</td>
                                <td>@if ($c['phone'])<a class="hz-link" href="tel:{{ $c['phone'] }}">{{ $c['phone'] }}</a>@else — @endif</td>
                                <td>{{ $c['email'] }}</td>
                                <td>@if ($c['role'])<span class="hz-badge">{{ $c['role'] }}</span>@endif</td>
                                <td>{{ $c['birthYear'] ?? '—' }}</td>
                                <td>{{ $c['province'] ?? '—' }}</td>
                                <td style="white-space:nowrap">{{ $P::when($c['at']) }}</td>
                                <td>
                                    @if ($zalo = $P::zaloUrl($c['parentPhone'] ?: $c['phone']))
                                        <x-filament::button tag="a" size="sm" color="gray" :href="$zalo" target="_blank" rel="noopener">Zalo</x-filament::button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="hz-muted" style="text-align:center;padding:24px">Chưa có khách nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pages > 1)
                <div class="hz-pager">
                    <span>{{ $total }} khách</span>
                    <span class="hz-row">
                        <x-filament::button size="sm" color="gray" wire:click="$set('pageNumber', {{ $pageNumber - 1 }})" :disabled="$pageNumber <= 1">‹ Trước</x-filament::button>
                        <span style="align-self:center">Trang {{ $pageNumber }} / {{ $pages }}</span>
                        <x-filament::button size="sm" color="gray" wire:click="$set('pageNumber', {{ $pageNumber + 1 }})" :disabled="$pageNumber >= $pages">Sau ›</x-filament::button>
                    </span>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
