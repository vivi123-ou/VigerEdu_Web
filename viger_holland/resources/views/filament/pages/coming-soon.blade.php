<x-filament-panels::page>
    <x-filament::section>
        <div style="text-align:center;padding:32px 12px">
            <div style="width:56px;height:56px;border-radius:50%;background:#f3f4f6;display:grid;place-items:center;margin:0 auto 14px;color:#6b7280">
                <x-filament::icon icon="heroicon-o-clock" style="width:28px;height:28px" />
            </div>
            <h3 style="margin:0 0 8px;font-size:17px;font-weight:600">Phần này đang làm, dự kiến xong ở giai đoạn {{ $item['stage'] }}</h3>
            <p style="margin:0 auto 18px;max-width:560px;color:#6b7280">{{ $item['help']['intro'] ?? '' }}</p>
            <x-filament::button tag="a" :href="\App\Support\AdminMenu::homeUrl()" color="gray" icon="heroicon-o-home">
                Về trang chủ quản trị
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
