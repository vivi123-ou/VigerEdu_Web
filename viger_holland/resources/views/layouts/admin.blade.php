@php
    use App\Support\AdminMenu;
    $currentKey = $menuKey ?? 'home';
    $currentItem = AdminMenu::find($currentKey);
    $currentGroup = collect(AdminMenu::groups())->first(fn ($group) => collect($group['items'])->contains('key', $currentKey));
    $user = auth()->user();
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Quản trị') – {{ setting('test_name') }}</title>
    <link rel="icon" href="{{ media_url(setting('favicon_path', 'img/favicon.png')) }}">
    @include('partials.fonts')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>:root { --brand: {{ setting('primary_color', '#c1121f') }}; --ink: {{ setting('text_color', '#12263f') }}; }</style>
</head>
<body class="admin">
    <div class="layout">
        {{-- Menu trái (nền tối) --}}
        <aside id="sidebar" class="sidebar" aria-label="Menu quản trị">
            <a class="sidebar__brand" href="{{ AdminMenu::homeUrl() }}">
                <img src="{{ media_url(setting('logo_path', 'img/logo.png')) }}" alt="" width="34" height="34">
                <span>Quản trị <strong>{{ setting('site_name') }}</strong></span>
            </a>

            <div class="sidebar__user">
                <span class="sidebar__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <span>
                    <strong>{{ $user->name }}</strong>
                    <small><span class="dot-online" aria-hidden="true"></span> {{ $user->roleLabel() }}</small>
                </span>
            </div>

            <nav class="sidebar__nav">
                <ul class="sidebar__list">
                    <li>
                        <a class="sidebar__link" href="{{ AdminMenu::homeUrl() }}">
                            @include('partials.icon', ['name' => 'arrow-left', 'size' => 18])
                            <span class="sidebar__label">Về trang quản trị</span>
                        </a>
                    </li>
                </ul>
                @foreach (AdminMenu::groups() as $group)
                    {{-- Mỗi người chỉ thấy mục của vai trò mình --}}
                    @php $visibleItems = array_filter($group['items'], fn ($item) => $user->canAccessMenu($item['key'])); @endphp
                    @continue(empty($visibleItems))
                    @isset($group['title'])
                        <p class="sidebar__title">{{ $group['title'] }}</p>
                    @endisset
                    <ul class="sidebar__list">
                        @foreach ($visibleItems as $item)
                            <li>
                                <a href="{{ AdminMenu::url($item) }}"
                                   @class(['sidebar__link', 'is-active' => $item['key'] === $currentKey, 'is-soon' => isset($item['stage'])])
                                   @if ($item['key'] === $currentKey) aria-current="page" @endif>
                                    {{ svg($item['icon'], '', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) }}
                                    <span class="sidebar__label">{{ $item['label'] }}</span>
                                    @isset($item['stage'])
                                        <span class="tag">Sắp có</span>
                                    @endisset
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </nav>
        </aside>
        <div class="sidebar-scrim" data-toggle-sidebar></div>

        <div class="main">
            {{-- Thanh trên (màu chủ đạo) --}}
            <header class="topbar">
                <button class="topbar__burger" type="button" data-toggle-sidebar aria-controls="sidebar" aria-expanded="false" aria-label="Mở menu">@include('partials.icon', ['name' => 'menu', 'size' => 22])</button>
                <a class="topbar__btn" href="{{ route('home') }}" target="_blank" rel="noopener">@include('partials.icon', ['name' => 'external', 'size' => 16]) <span>Xem trang test như khách</span></a>
                @if (filled(setting('main_site_url')))
                    <a class="topbar__btn topbar__btn--soft" href="{{ setting('main_site_url') }}" target="_blank" rel="noopener"><span>Web chính</span></a>
                @endif

                <details class="dropdown dropdown--right topbar__account">
                    <summary class="topbar__btn" aria-label="Tài khoản">
                        <span class="topbar__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        <span class="topbar__label">{{ $user->name }}</span> @include('partials.icon', ['name' => 'chevron-down', 'size' => 16])
                    </summary>
                    <div class="dropdown__menu">
                        <span class="dropdown__muted">{{ $user->username }} · {{ $user->roleLabel() }}</span>
                        @if (filled(setting('crm_url')))
                            <a href="{{ setting('crm_url') }}" target="_blank" rel="noopener">Mở CRM</a>
                        @endif
                        <form method="post" action="{{ route('filament.admin.auth.logout') }}">
                            @csrf
                            <button type="submit">@include('partials.icon', ['name' => 'logout', 'size' => 16]) Đăng xuất</button>
                        </form>
                    </div>
                </details>
            </header>

            <main class="content">
                <div class="page-head">
                    <div class="page-head__left">
                        <h1 class="page-head__title">@yield('title', 'Quản trị')</h1>
                        @if (filled($currentItem['help'] ?? null))
                            <button class="help-btn" type="button" data-open-help aria-label="Hướng dẫn trang này" title="Hướng dẫn">?</button>
                        @endif
                    </div>
                    <nav class="breadcrumb" aria-label="Vị trí">
                        <a href="{{ AdminMenu::homeUrl() }}">Trang chủ</a>
                        @if ($currentKey !== 'home')
                            @isset($currentGroup['title'])<span>›</span> <span>{{ $currentGroup['title'] }}</span>@endisset
                            <span>›</span> <span>{{ $currentItem['label'] ?? '' }}</span>
                        @endif
                    </nav>
                </div>

                @if (session('status'))
                    <div class="alert alert--success" role="status">@include('partials.icon', ['name' => 'check', 'size' => 18]) {{ session('status') }}</div>
                @endif

                {{-- Lỗi của thao tác (không gắn với một ô nhập cụ thể) --}}
                @foreach (['question', 'set', 'order', 'file'] as $errorKey)
                    @error($errorKey)
                        <div class="alert alert--error" role="alert">{{ $message }}</div>
                    @enderror
                @endforeach

                @yield('content')
            </main>
        </div>
    </div>

    @if (filled($currentItem['help'] ?? null))
        <dialog class="help-dialog" id="help-dialog">
            <h2>Trang này dùng để làm gì?</h2>
            @include('admin.partials.help-content', ['help' => $currentItem['help']])
            <form method="dialog"><button class="btn btn--primary" type="submit">Đã hiểu</button></form>
        </dialog>
    @endif

    <script src="{{ asset('js/admin.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
