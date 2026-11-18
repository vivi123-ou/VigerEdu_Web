{{-- Thanh trên khu quản trị: xem trang khách, web chính (chỉ quản trị viên), mở CRM (khi đã cài địa chỉ), tên và vai trò. --}}
@php($user = auth()->user())
<div style="display:flex;align-items:center;gap:6px;margin-inline-end:8px">
    <x-filament::button tag="a" :href="route('home')" target="_blank" rel="noopener" color="gray" size="sm" outlined icon="heroicon-o-arrow-top-right-on-square">
        Xem trang làm bài như khách
    </x-filament::button>
    @if ($user?->isAdmin() && filled(setting('main_site_url')))
        <x-filament::link :href="setting('main_site_url')" target="_blank" rel="noopener" color="gray" icon="heroicon-o-globe-alt" style="padding:0 8px">Web chính</x-filament::link>
    @endif
    @if (filled(setting('crm_url')))
        <x-filament::link :href="setting('crm_url')" target="_blank" rel="noopener" color="gray" icon="heroicon-o-link" style="padding:0 8px">Mở CRM</x-filament::link>
    @endif
    @if ($user)
        <span style="display:flex;flex-direction:column;line-height:1.2;text-align:right;padding-inline-start:8px">
            <strong style="font-size:13px">{{ $user->name }}</strong>
            <small style="font-size:12px;color:#6b7280">{{ $user->roleLabel() }}</small>
        </span>
    @endif
</div>
