<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="{{ route('home') }}">
            <img class="brand__logo" src="{{ asset(setting('logo_path', 'img/logo.png')) }}" alt="{{ setting('site_name') }}" width="36" height="36">
            <span class="brand__text">
                <span class="brand__name">{{ setting('site_name') }}</span>
                <span class="brand__tagline">{{ setting('test_name') }}</span>
            </span>
        </a>

        @if (filled(setting('main_site_url')))
            <a class="site-header__back" href="{{ setting('main_site_url') }}">
                <span aria-hidden="true">←</span> {{ setting('main_site_link_text', 'Về trang chủ') }}
            </a>
        @endif
    </div>
</header>
