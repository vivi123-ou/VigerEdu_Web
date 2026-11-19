{{--
    Layout trang khách (H3, theo mẫu docs/mau_giao_dien): đầu trang chỉ có logo, chân trang gọn.
    Màu, font, chữ lấy từ Cài đặt. Trang có dữ liệu riêng của khách (làm bài, kết quả) khai báo @section('noindex').
--}}
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', setting('seo_home_title', setting('test_name')))</title>
    <meta name="description" content="@yield('description', setting('seo_home_description'))">
    @hasSection('noindex')
        <meta name="robots" content="noindex, nofollow">
    @endif
    <meta property="og:title" content="@yield('title', setting('seo_home_title', setting('test_name')))">
    <meta property="og:description" content="@yield('description', setting('seo_home_description'))">
    <meta property="og:type" content="website">
    @if (filled(setting('seo_share_image')))
        <meta property="og:image" content="{{ media_url(setting('seo_share_image')) }}">
    @endif
    <link rel="icon" href="{{ media_url(setting('favicon_path', 'img/favicon.png')) }}">
    @include('partials.fonts')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/khach.css') }}">
    <style>:root { --brand: {{ setting('primary_color', '#c1121f') }}; --ink: {{ setting('text_color', '#12263f') }}; }</style>
    @stack('styles')
</head>
<body class="k">
    @include('partials.khach-icons')
    <a class="skip-link" href="#noi-dung">Bỏ qua, tới nội dung</a>

    <div class="kpage">
        @hasSection('header')
            @yield('header')
        @else
            @include('partials.site-header')
        @endif

        <main id="noi-dung" class="kmain">
            @yield('content')
        </main>

        @include('partials.site-footer')
    </div>

    {{-- Chỗ gắn chatbot (độc lập). Mã nhúng do quản trị viên dán trong Cài đặt; để trống là không có chatbot. --}}
    <div id="viger-chatbot"></div>
    @if (filled(setting('chatbot_embed_code')))
        {!! setting('chatbot_embed_code') !!}
    @elseif (filled(config('holland.chat_widget_url')))
        <script src="{{ config('holland.chat_widget_url') }}" defer></script>
    @endif

    @stack('scripts')
</body>
</html>
