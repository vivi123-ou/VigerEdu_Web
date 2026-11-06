{{-- Icon nét mảnh dùng chung. Cách dùng: @include('partials.icon', ['name' => 'menu']) --}}
<svg class="icon {{ $class ?? '' }}" width="{{ $size ?? 20 }}" height="{{ $size ?? 20 }}" viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16"/>
            @break
        @case('bell')
            <path d="M6 9a6 6 0 1 1 12 0c0 6 2 7.5 2 7.5H4S6 15 6 9"/><path d="M10 20a2 2 0 0 0 4 0"/>
            @break
        @case('chevron-down')
            <path d="m7 10 5 5 5-5"/>
            @break
        @case('arrow-left')
            <path d="M19 12H5M11 6l-6 6 6 6"/>
            @break
        @case('external')
            <path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>
            @break
        @case('search')
            <circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/>
            @break
        @case('user')
            <circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-3.5 4.5-5 8-5s6.5 1.5 8 5"/>
            @break
        @case('grid')
            <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>
            @break
        @case('home')
            <path d="M4 11 12 4l8 7"/><path d="M6 10v10h4v-6h4v6h4V10"/>
            @break
        @case('list')
            <path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>
            @break
        @case('form')
            <rect x="4" y="3" width="16" height="18" rx="1.5"/><path d="M8 8h8M8 12h8M8 16h5"/>
            @break
        @case('sliders')
            <path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>
            @break
        @case('hexagon')
            <path d="M12 3 20 7.5v9L12 21l-8-4.5v-9z"/>
            @break
        @case('image')
            <rect x="3" y="4" width="18" height="16" rx="1.5"/><circle cx="9" cy="10" r="1.75"/><path d="m21 16-5-5-9 9"/>
            @break
        @case('chart')
            <path d="M4 20V4M4 20h16"/><path d="M8 16v-5M12 16V8M16 16v-3"/>
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/>
            @break
        @case('code')
            <path d="m8 7-5 5 5 5M16 7l5 5-5 5M14 4l-4 16"/>
            @break
        @case('palette')
            <path d="M12 3a9 9 0 1 0 0 18c1.2 0 1.8-.9 1.4-2-.5-1.2.3-2.5 1.6-2.5H17a4 4 0 0 0 4-4C21 7 17 3 12 3z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="15" cy="7.5" r="1"/>
            @break
        @case('users')
            <circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1-3.5 3.6-5 6.5-5s5.5 1.5 6.5 5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 15c2 .6 3.2 2.2 3.5 5"/>
            @break
        @case('send')
            <path d="M21 3 10 14M21 3l-7 18-4-7-7-4z"/>
            @break
        @case('table')
            <rect x="3" y="4" width="18" height="16" rx="1.5"/><path d="M3 10h18M3 15h18M9 4v16"/>
            @break
        @case('link')
            <path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M2 12h3M19 12h3M4.9 19.1 7 17M17 7l2.1-2.1"/>
            @break
        @case('shield')
            <path d="M12 3 4 6v6c0 4.5 3.4 8 8 9 4.6-1 8-4.5 8-9V6z"/>
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14"/>
            @break
        @case('edit')
            <path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>
            @break
        @case('trash')
            <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>
            @break
        @case('refresh')
            <path d="M20 11a8 8 0 0 0-14.6-4.5M4 4v4h4"/><path d="M4 13a8 8 0 0 0 14.6 4.5M20 20v-4h-4"/>
            @break
        @case('eye')
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>
            @break
        @case('copy')
            <rect x="8" y="8" width="12" height="12" rx="1.5"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/>
            @break
        @case('logout')
            <path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/>
            @break
        @case('check')
            <path d="m5 12.5 4.5 4.5L19 7.5"/>
            @break
    @endswitch
</svg>
