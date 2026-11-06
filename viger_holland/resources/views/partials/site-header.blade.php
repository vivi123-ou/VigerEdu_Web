@php $menuLinks = \App\Support\ContentPages::links(setting('header_menu')); @endphp
<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="{{ route('home') }}">
            <img class="brand__logo" src="{{ media_url(setting('logo_path', 'img/logo.png')) }}" alt="{{ setting('site_name') }}" width="44" height="44">
            <span class="brand__text">
                <span class="brand__name">{{ setting('site_name') }}</span>
                <span class="brand__tagline">{{ setting('test_name') }}</span>
            </span>
        </a>

        @if ($menuLinks)
            <details class="site-nav">
                <summary class="site-nav__toggle" aria-label="Menu">@include('partials.icon', ['name' => 'menu', 'size' => 22])</summary>
                <nav class="site-nav__list" aria-label="Menu chính">
                    @foreach ($menuLinks as $link)
                        <a href="{{ $link['url'] }}" @if ($link['new_tab']) target="_blank" rel="noopener" @endif>{{ $link['label'] }}</a>
                    @endforeach
                </nav>
            </details>
        @endif

        @if (filled(setting('main_site_url')))
            <a class="site-header__back" href="{{ setting('main_site_url') }}" aria-label="{{ setting('main_site_link_text', 'Về trang chủ') }}">
                @include('partials.icon', ['name' => 'arrow-left', 'size' => 18])
                <span class="site-header__back-text">{{ setting('main_site_link_text', 'Về trang chủ') }}</span>
            </a>
        @endif
    </div>
</header>
