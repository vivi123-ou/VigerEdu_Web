<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập — Viger CRM</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/crm.css') }}">
</head>
<body class="crm crm-auth">
    <div class="auth-hero">
        <div class="auth-hero-inner">
            <img src="{{ asset('img/logo.png') }}" alt="Viger" class="auth-logo">
            <h1>Quản lý khách hàng<br>du học Đức tập trung.</h1>
            <p>Theo dõi leads từ Website, Holland test và Chatbot — phân công tư vấn, nhắc lịch hẹn và đo tỷ lệ chuyển đổi trên một màn hình.</p>
            <ul>
                <li>Leads tự động đổ về từ mọi kênh</li>
                <li>Lịch hẹn &amp; công việc theo từng nhân viên</li>
                <li>Báo cáo chuyển đổi theo thời gian thực</li>
            </ul>
        </div>
    </div>

    <div class="auth-form-wrap">
        <form method="POST" action="{{ route('crm.login') }}" class="auth-form">
            @csrf
            <h2>Đăng nhập</h2>
            <p class="muted">Chào mừng trở lại Viger CRM</p>

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <label class="field">
                <span>Tên đăng nhập</span>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
            </label>

            <label class="field">
                <span>Mật khẩu</span>
                <div class="input-icon">
                    <input type="password" name="password" id="password" required autocomplete="current-password">
                    <button type="button" class="toggle-pass" data-toggle-pass aria-label="Hiện mật khẩu">
                        <svg class="eye-on" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-off" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-6.5 0-10-7-10-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24M1 1l22 22"/></svg>
                    </button>
                </div>
            </label>

            <label class="check"><input type="checkbox" name="remember"> Ghi nhớ đăng nhập</label>

            <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
            <p class="auth-foot muted">© {{ date('Y') }} VIGER.EDU.VN</p>
        </form>
    </div>

    <script>
        document.querySelector('[data-toggle-pass]').addEventListener('click', function (e) {
            e.preventDefault();
            var input = document.getElementById('password');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            this.classList.toggle('is-visible', show);
            this.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
            input.focus();
        });
    </script>
</body>
</html>
