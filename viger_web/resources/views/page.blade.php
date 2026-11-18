@extends('layouts.app')

@section('title', $page->title)

@section('content')
  <main class="main-content">
    <!-- PAGE HEADER SECTION -->
    <section
      class="cta-section animate-hidden"
      style="
        margin: 0;
        border-radius: 0;
        padding: 10rem 0 6rem;
        background-image:
          linear-gradient(rgba(18, 38, 63, 0.85), rgba(18, 38, 63, 0.95)),
          url('{{ $page->bannerImageUrl ?: asset('img/logo.png') }}');
        background-size: cover;
        background-position: center;
        border-bottom: 4px solid var(--accent-main);
      "
    >
      <div class="page-container">
        <div class="text-center animate-hidden">
          <h1 class="hero-heading" style="color: white;">{{ $page->title }}</h1>
          @if ($page->subtitle)
            <p class="cta-desc" style="max-width: 700px; margin: 1.5rem auto 0; font-weight: 300;">
              {{ $page->subtitle }}
            </p>
          @endif
        </div>
      </div>
    </section>

    <!-- MAIN CONTENT SECTION -->
    <section style="padding: 6rem 0; background-color: var(--bg-main);">
      <div class="page-container" style="max-width: 900px; margin: 0 auto;">
        @if ($page->content)
          <div class="prose" style="font-size: 1.1rem; line-height: 1.8; color: var(--text-primary);">
            {!! $page->content !!}
          </div>
        @endif

        @if (!empty($page->sections) && is_array($page->sections))
          <div style="margin-top: 3rem;">
            @foreach ($page->sections as $section)
              <div style="background: var(--bg-white); padding: 2rem; border-radius: var(--radius-xl); box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; border-left: 4px solid var(--accent-main);">
                <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--text-primary);">
                  {{ $section['title'] ?? '' }}
                </h3>
                <p style="font-size: 1rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">
                  {{ $section['body'] ?? '' }}
                </p>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </section>
  </main>
@endsection

