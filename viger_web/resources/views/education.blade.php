@extends('layouts.app')

@section('title', __('education.page_title'))

@php
  $page = \App\Models\Page::where('slug', 'giao-duc-tai-duc')->first();
  $bannerImg = ($page && $page->bannerImageUrl) ? $page->bannerImageUrl : 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1600&q=80';

  // Nội dung của Page trong admin chỉ dùng khi có bản đúng ngôn ngữ đang xem,
  // không có => dùng nội dung mặc định trong lang/{locale}/education.php.
  $locale = app()->getLocale();
  $pageText = fn (string $field) => $page?->getTranslation($field, $locale, false);
  $pageTitle = filled($pageText('title')) ? $pageText('title') : __('education.hero_title');
  $pageSubtitle = filled($pageText('subtitle')) ? $pageText('subtitle') : __('education.hero_subtitle');
  $pageContent = $pageText('content');

  // Icon của 4 card ưu điểm (phần bên trong thẻ <svg>), chữ lấy từ lang theo cùng vị trí.
  $featureIcons = [
    '<path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path>',
    '<circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path>',
    '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
    '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>',
  ];
  $features = collect(__('education.features'))
    ->map(fn (array $feature, int $index) => $feature + ['icon' => $featureIcons[$index] ?? ''])
    ->all();
@endphp

@section('content')
  <main class="main-content">
    <!-- HEADER BANNER -->
    <section
      class="cta-section animate-hidden"
      style="
        margin: 0;
        border-radius: 0;
        padding: 10rem 0 6rem;
        background-image:
          linear-gradient(135deg, rgba(20, 20, 20, 0.9) 0%, rgba(193, 18, 31, 0.8) 100%),
          url('{{ $bannerImg }}');
        background-size: cover;
        background-position: center;
        border-bottom: 4px solid var(--accent-alt);
      "
    >
      <div class="page-container">
        <div class="text-center animate-hidden">
          <div class="section-pretitle" style="justify-content: center; margin-bottom: 1rem">
            <span style="color: var(--accent-alt); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;"
              >{{ __('education.hero_pretitle') }}</span
            >
          </div>
          <h1 class="hero-heading" style="color: white; margin-bottom: 1rem">
            {{ $pageTitle }}
          </h1>
          <p class="cta-desc" style="max-width: 750px; margin: 1.5rem auto 0; font-weight: 300;">
            {{ $pageSubtitle }}
          </p>
        </div>
      </div>
    </section>

    <!-- TỔNG QUAN -->
    <section style="padding: 6rem 0; background-color: var(--bg-main)">
      <div class="page-container">
        <div class="hero-grid">
          <!-- Cột trái: Text -->
          <div class="hero-text animate-hidden">
            <div class="section-pretitle">
              <span style="color: var(--accent-alt-hover); background-color: #fff9e6; padding: 0.35rem 1rem; border-radius: var(--radius-full); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;"
                >{{ __('education.overview_label') }}</span
              >
            </div>
            <h2 class="about-heading" style="margin-top: 1.5rem">
              {{ __('education.overview_heading') }}
            </h2>

            @if (filled($pageContent))
              <div class="about-desc">
                {!! $pageContent !!}
              </div>
            @else
              <p class="about-desc">{{ __('education.overview_paragraph_1') }}</p>

              <p class="about-desc" style="margin-bottom: 0">{{ __('education.overview_paragraph_2') }}</p>
            @endif
          </div>

          <!-- Cột phải: Hình ảnh -->
          <div class="hero-image animate-hidden delay-100">
            <img
              src="https://images.unsplash.com/photo-1599940824399-b87987ceb72a?auto=format&fit=crop&w=800&q=80"
              alt="{{ __('education.overview_image_alt') }}"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- CÁC ƯU ĐIỂM NỔI BẬT (FEATURES GRID) -->
    <section style="padding: 6rem 0; background-color: var(--bg-white)">
      <div class="page-container">
        <div class="text-center animate-hidden" style="margin-bottom: 4rem">
          <h2 class="section-title">{{ __('education.features_heading') }}</h2>
          <p class="about-desc" style="max-width: 600px; margin: 0 auto">
            {{ __('education.features_description') }}
          </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2.5rem;">
          @foreach ($features as $feature)
            <div
              class="timeline-step animate-hidden delay-{{ $loop->iteration * 100 }}"
              style="display: flex; flex-direction: column; align-items: flex-start;"
            >
              <div style="width: 56px; height: 56px; background: rgba(193, 18, 31, 0.1); border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; color: var(--accent-main); margin-bottom: 1.5rem;">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="28"
                  height="28"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  {!! $feature['icon'] !!}
                </svg>
              </div>
              <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem;">
                {{ $feature['title'] }}
              </h3>
              <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">
                {{ $feature['body'] }}
              </p>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <!-- CTA SECTION -->
    <section class="cta-section animate-hidden">
      <div class="page-container">
        <div class="section-pretitle" style="justify-content: center">
          <span>{{ __('education.cta_pretitle') }}</span>
        </div>
        <h2 class="cta-title">{{ __('education.cta_title') }}</h2>
        <p class="cta-desc">{{ __('education.cta_description') }}</p>
        <a class="cta-pill" href="/lien-he">{{ __('education.cta_button') }}</a>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/animations.js') }}"></script>
@endpush
