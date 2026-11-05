<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Đăng nhập quản trị – {{ setting('test_name') }}</title>
    <link rel="icon" href="{{ asset(setting('favicon_path', 'img/favicon.png')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>:root { --brand: {{ setting('primary_color', '#c1121f') }}; --ink: {{ setting('text_color', '#12263f') }}; }</style>
</head>
<body class="auth">
    <main class="auth-card">
        <img class="auth-card__logo" src="{{ asset(setting('logo_path', 'img/logo.png')) }}" alt="{{ setting('site_name') }}" width="56" height="56">
        <h1>Đăng nhập quản trị</h1>
        <p class="auth-card__sub">{{ setting('test_name') }} · {{ setting('site_name') }}</p>

        @if (session('status'))
            <div class="alert alert--success" role="status">{{ session('status') }}</div>
        @endif

        <form method="post" action="{{ route('admin.login.store') }}" novalidate>
            @csrf
            <div class="field">
                <label for="username">Tên đăng nhập hoặc email</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus
                       @error('username') aria-invalid="true" aria-describedby="username-error" @enderror>
                @error('username')
                    <p class="field__error" id="username-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password')
                    <p class="field__error" id="password-error">{{ $message }}</p>
                @enderror
            </div>

            <label class="check">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                Ghi nhớ đăng nhập trên máy này
            </label>

            <button class="btn btn--primary btn--block" type="submit">Đăng nhập</button>
        </form>

        <p class="auth-card__hint">Quên mật khẩu? Liên hệ quản trị viên để được cấp lại.</p>
    </main>
</body>
</html>
