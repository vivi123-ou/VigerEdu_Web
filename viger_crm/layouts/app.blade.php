<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Viger CRM</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/crm.css') }}">
    @stack('styles')
</head>
<body class="crm">
    @php
        $crmUser = auth()->user();
        $crmUserName = $crmUser->full_name ?: ($crmUser->name ?: $crmUser->username);

        // Breadcrumb tự dựng từ config/crm.php: Nhóm › Chức năng
        $crumbs = [];
        foreach (config('crm.menu') as $group) {
            foreach ($group['items'] as $item) {
                if ($item['route'] && request()->routeIs($item['active'] ?? $item['route'])) {
                    $crumbs = [$group['label'], $item['name']];
                }
            }
        }
    @endphp

    <div class="crm-shell">
        @include('crm::partials.sidebar')
        <div class="crm-overlay" data-sidebar-close></div>

        <div class="crm-main">
            <header class="crm-topbar">
                <button class="crm-icon-btn crm-menu-btn" data-sidebar-toggle aria-label="Mở menu">
                    <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                {{-- Breadcrumb: trang chi tiết có thể nối thêm bằng @section('breadcrumb') --}}
                <nav class="crm-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('crm.dashboard') }}" aria-label="Trang chủ CRM">
                        <svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    </a>
                    @foreach ($crumbs as $c)
                        <span class="sep">/</span>
                        <span class="{{ $loop->last && ! View::hasSection('breadcrumb') ? 'current' : '' }}">{{ $c }}</span>
                    @endforeach
                    @hasSection('breadcrumb')
                        <span class="sep">/</span><span class="current">@yield('breadcrumb')</span>
                    @endif
                </nav>

                <div class="crm-topbar-right">
                    <div class="crm-search">
                        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input type="search" placeholder="Tìm khách hàng, số điện thoại...">
                    </div>

                    <details class="crm-dropdown">
                        @php $notif = \App\Http\Controllers\Crm\NotificationController::feed($crmUser); @endphp
                        <summary class="crm-icon-btn" aria-label="Thông báo{{ $notif['unread'] ? ' ('.$notif['unread'].' mới)' : '' }}" data-notif-toggle>
                            <svg viewBox="0 0 24 24"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                            @if ($notif['unread'])
                                <span class="crm-badge" data-notif-badge>{{ $notif['unread'] > 9 ? '9+' : $notif['unread'] }}</span>
                            @endif
                        </summary>
                        <div class="crm-menu crm-menu-wide">
                            <p class="crm-menu-title">
                                Thông báo
                                @if ($notif['unread'])<small class="muted" data-notif-count>{{ $notif['unread'] }} mới</small>@endif
                            </p>
                            <div class="crm-notice-list">
                                @forelse ($notif['items'] as $n)
                                    <a href="{{ route('crm.leads.show', $n->lead_id) }}#history"
                                       class="crm-notice {{ ! $notif['seenAt'] || $n->created_at->gt($notif['seenAt']) ? 'is-unread' : '' }}">
                                        <span class="tl-icon tl-{{ $n->type }}"><svg viewBox="0 0 24 24">{!! $n->typeIcon() !!}</svg></span>
                                        <span class="crm-notice-body">
                                            <b>{{ $n->lead->full_name }}</b>
                                            <span class="crm-notice-text">{{ \Illuminate\Support\Str::limit($n->content, 80) }}</span>
                                            <span class="muted">{{ $n->user ? $n->user->displayName().' · ' : '' }}{{ $n->created_at->diffForHumans() }}</span>
                                        </span>
                                    </a>
                                @empty
                                    <p class="crm-notice-empty">Chưa có thông báo nào.</p>
                                @endforelse
                            </div>
                        </div>
                    </details>

                    <details class="crm-dropdown">
                        <summary class="crm-user">
                            <span class="crm-avatar">{{ mb_strtoupper(mb_substr($crmUserName, 0, 1)) }}</span>
                            <span class="crm-user-name">{{ $crmUserName }}<small>{{ $crmUser->role === 'ADMIN' ? 'Quản trị viên' : 'Tư vấn viên' }}</small></span>
                            <svg class="chev" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="crm-menu">
                            <a href="#">Hồ sơ cá nhân</a>
                            <a href="#">Đổi mật khẩu</a>
                            <form method="POST" action="{{ route('crm.logout') }}">
                                @csrf
                                <button type="submit" class="danger">Đăng xuất</button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <main class="crm-content">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-sidebar-toggle],[data-sidebar-close]').forEach(function (el) {
            el.addEventListener('click', function () { document.body.classList.toggle('sidebar-open'); });
        });
        // Mở chuông => đánh dấu đã xem (giữ chấm "mới" trên từng dòng tới lần tải trang sau)
        var notifBtn = document.querySelector('[data-notif-toggle]');
        if (notifBtn) notifBtn.addEventListener('click', function () {
            var badge = document.querySelector('[data-notif-badge]');
            if (!badge) return;
            fetch(@json(route('crm.notifications.read')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
            }).then(function (r) {
                if (!r.ok) return;
                badge.remove();
                var count = document.querySelector('[data-notif-count]');
                if (count) count.remove();
            });
        });

        // Đóng dropdown header khi bấm ra ngoài
        document.addEventListener('click', function (e) {
            document.querySelectorAll('.crm-dropdown[open]').forEach(function (d) {
                if (!d.contains(e.target)) d.removeAttribute('open');
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
