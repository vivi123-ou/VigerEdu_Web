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
    @endswitch
</svg>
