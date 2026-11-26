{{-- Menu khai báo tại config/crm.php --}}
<aside class="crm-sidebar">
    <a href="{{ route('crm.dashboard') }}" class="crm-brand">
        <img src="{{ asset('img/logo.png') }}" alt="Viger">
        <span>VIGER <b>CRM</b></span>
    </a>

    <nav class="crm-nav">
        @foreach (config('crm.menu') as $group)
            @php
                // Mục không có quyền: ẩn hẳn; nhóm không còn mục nào: ẩn cả nhóm
                $group['items'] = array_values(array_filter($group['items'], fn ($i) => \App\Support\CrmMenu::visible($i)));
                $groupActive = collect($group['items'])->contains(
                    fn ($i) => \App\Support\CrmMenu::isActive($i)
                );
            @endphp
            @continue(empty($group['items']))

            <details class="crm-nav-group" @if ($groupActive || $loop->first) open @endif>
                <summary class="crm-nav-label">
                    {{ $group['label'] }}
                    <svg class="chev" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                </summary>

                @foreach ($group['items'] as $item)
                    @php $active = \App\Support\CrmMenu::isActive($item); @endphp
                    <a href="{{ \App\Support\CrmMenu::url($item) }}"
                       class="crm-nav-link {{ $active ? 'is-active' : '' }} {{ $item['route'] ? '' : 'is-soon' }}"
                       @if ($active) aria-current="page" @endif
                       @unless ($item['route']) title="Chức năng đang phát triển" @endunless>
                        <svg viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                        <span>{{ $item['name'] }}</span>
                    </a>
                @endforeach
            </details>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('crm.logout') }}" class="crm-logout">
        @csrf
        <button type="submit" class="crm-nav-link">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            <span>Đăng xuất</span>
        </button>
    </form>
</aside>
