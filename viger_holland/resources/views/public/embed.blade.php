{{-- Khối "Trắc nghiệm Holland" rút gọn để nhúng vào web chính (iframe). Không có đầu trang / chân trang. --}}
@php $image = media_url(setting('embed_image')); @endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ setting('embed_title', setting('test_name')) }}</title>
    @include('partials.fonts')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>:root { --brand: {{ setting('primary_color', '#c1121f') }}; --ink: {{ setting('text_color', '#12263f') }}; } body { background: transparent; }</style>
</head>
<body class="embed">
    <article @class(['embed-card', 'embed-card--no-image' => ! $image])>
        @if ($image)
            <img class="embed-card__img" src="{{ $image }}" alt="">
        @endif
        <div class="embed-card__body">
            <h2 class="embed-card__title">{{ setting('embed_title', setting('intro_title')) }}</h2>
            @if (filled(setting('embed_subtitle')))
                <p class="embed-card__text">{{ setting('embed_subtitle') }}</p>
            @endif
            <a class="btn btn--primary" href="{{ route('home', ['utm_source' => 'viger.edu.vn', 'utm_medium' => 'embed']) }}" target="_blank" rel="noopener">
                {{ setting('embed_button_text', setting('intro_button_text')) }} <span aria-hidden="true">→</span>
            </a>
        </div>
    </article>
    <script>
        // Báo chiều cao cho trang cha để iframe tự co giãn (embed.js).
        (function () {
            function send() { parent.postMessage({ type: 'holland-embed-height', height: document.documentElement.scrollHeight }, '*'); }
            window.addEventListener('load', send);
            window.addEventListener('resize', send);
        })();
    </script>
</body>
</html>
