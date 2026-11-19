{{-- Trang danh mục có thẻ (TabbedTablePage): thẻ ở trên, câu giải thích của thẻ, bảng sửa được. --}}
<x-filament-panels::page>
    <x-filament::tabs>
        @foreach ($this->tabs() as $key => [$label])
            <x-filament::tabs.item :active="$tab === $key" wire:click="setTab('{{ $key }}')">{{ $label }}</x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>
    @if (filled($this->tabs()[$tab][1] ?? null))
        <p style="margin:0;color:rgb(107 114 128);font-size:14px">{{ $this->tabs()[$tab][1] }}</p>
    @endif
    {{ $this->table }}
</x-filament-panels::page>
