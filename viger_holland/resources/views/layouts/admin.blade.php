@php
    use App\Support\AdminMenu;
    $currentKey = $menuKey ?? 'dashboard';
    $currentItem = AdminMenu::find($currentKey);
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Quản trị') – {{ setting('test_name') }}</title>
    <link rel="icon" href="{{ asset(setting('favicon_path', 'img/favicon.png')) }}">
    @include('partials.fonts')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>:root { --brand: {{ setting('primary_color', '#c1121f') }}; --ink: {{ setting('text_color', '#12263f') }}; }</style>
</head>
<body class="admin">
    <header class="topbar">
        <button class="topbar__burger" type="button" data-toggle-sidebar aria-controls="sidebar" aria-expanded="false" aria-label="Mở menu">@include('partials.icon', ['name' => 'menu', 'size' => 22])</button>

        <a class="topbar__brand" href="{{ route('admin.dashboard') }}">
            <img src="{{ asset(setting('logo_path', 'img/logo.png')) }}" alt="" width="28" height="28">
            <span class="topbar__brand-text">{{ setting('test_name') }}</span>
        </a>

        <form class="topbar__search" role="search" onsubmit="return false">
            @include('partials.icon', ['name' => 'search', 'size' => 16])
            <input type="search" placeholder="Tìm lượt làm bài (sắp có)" aria-label="Tìm lượt làm bài" disabled>
        </form>

        <nav class="topbar__actions" aria-label="Công cụ">
            <a class="topbar__btn topbar__btn--primary" href="{{ route('home') }}" target="_blank" rel="noopener">Xem trang test như khách @include('partials.icon', ['name' => 'external', 'size' => 15])</a>

            <span class="topbar__btn topbar__bell" title="Lượt chưa gửi CRM (sắp có)" aria-label="Thông báo">@include('partials.icon', ['name' => 'bell', 'size' => 19])</span>

            <details class="dropdown">
                <summary class="topbar__btn" aria-label="Công cụ">@include('partials.icon', ['name' => 'grid', 'size' => 18, 'class' => 'topbar__mobile-icon'])<span class="topbar__label">Công cụ</span> @include('partials.icon', ['name' => 'chevron-down', 'size' => 16])</summary>
                <div class="dropdown__menu">
                    @if (filled(setting('main_site_url')))
                        <a href="{{ setting('main_site_url') }}" target="_blank" rel="noopener">Web chính</a>
                    @endif
                    @if (filled(setting('crm_url')))
                        <a href="{{ setting('crm_url') }}" target="_blank" rel="noopener">CRM</a>
                    @else
                        <span class="dropdown__muted">CRM (chưa kết nối)</span>
                    @endif
                </div>
            </details>

            <details class="dropdown dropdown--right">
                <summary class="topbar__btn" aria-label="Tài khoản">@include('partials.icon', ['name' => 'user', 'size' => 18, 'class' => 'topbar__mobile-icon'])<span class="topbar__label">{{ auth()->user()->name }}</span> @include('partials.icon', ['name' => 'chevron-down', 'size' => 16])</summary>
                <div class="dropdown__menu">
                    <span class="dropdown__muted">{{ auth()->user()->username }} · {{ auth()->user()->roleLabel() }}</span>
                    <form method="post" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit">Đăng xuất</button>
                    </form>
                </div>
            </details>
        </nav>
    </header>

    <div class="layout">
        <aside id="sidebar" class="sidebar" aria-label="Menu quản trị">
            @foreach (AdminMenu::groups() as $group)
                @isset($group['title'])
                    <p class="sidebar__title">{{ $group['title'] }}</p>
                @endisset
                <ul class="sidebar__list">
                    @foreach ($group['items'] as $item)
                        <li>
                            <a href="{{ AdminMenu::url($item) }}"
                               @class(['sidebar__link', 'is-active' => $item['key'] === $currentKey, 'is-pending' => isset($item['stage'])])
                               @if ($item['key'] === $currentKey) aria-current="page" @endif>
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </aside>
        <div class="sidebar-scrim" data-toggle-sidebar></div>

        <main class="content">
            <div class="page-head">
                <h1 class="page-head__title">@yield('title', 'Quản trị')</h1>
                @if (filled($currentItem['help'] ?? null))
                    <button class="help-btn" type="button" data-open-help aria-label="Hướng dẫn trang này">?</button>
                @endif
            </div>

            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            @yield('content')
        </main>
    </div>

    @if (filled($currentItem['help'] ?? null))
        <dialog class="help-dialog" id="help-dialog">
            <h2>Hướng dẫn: {{ $currentItem['label'] }}</h2>
            <p>{{ $currentItem['help'] }}</p>
            <form method="dialog"><button class="btn btn--primary" type="submit">Đã hiểu</button></form>
        </dialog>
    @endif

    <script src="{{ asset('js/admin.js') }}" defer></script>
</body>
</html>
