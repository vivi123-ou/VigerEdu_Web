<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1, viewport-fit=cover"
    />
    <meta
      name="description"
      content="VIGER.EDU.VN — From Vietnam to Europe. Expert German education consultancy for Vietnamese students seeking world-class academic opportunities in Germany."
    />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    {{-- Tương đương layout:title-pattern="$CONTENT_TITLE - $LAYOUT_TITLE" --}}
    <title>@hasSection('title')@yield('title') - @endif VIGER.EDU.VN</title>

    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}" />
    <link href="{{ asset('styles.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/chatbot.css') }}" rel="stylesheet" />

    {{-- CSS riêng của từng trang --}}
    @stack('styles')
  </head>

  <body>
    {{-- Header --}}
    @include('partials.header')

    {{-- Nội dung chính (layout:fragment="content") --}}
    @yield('content')

    {{-- Footer --}}
    @include('partials.footer')

    {{-- Chatbot AI (nút nổi góc phải dưới) --}}
    @include('partials.chatbot')

    {{-- JS chung --}}
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/chatbot.js') }}"></script>

    {{-- JS riêng của từng trang (layout:fragment="scripts") --}}
    @stack('scripts')
  </body>
</html>