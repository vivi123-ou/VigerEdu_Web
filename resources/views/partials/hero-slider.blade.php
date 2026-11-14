{{--
  Banner slideshow tự chuyển.
  Dùng:  @include('partials.hero-slider')
  Hoặc truyền dữ liệu riêng:
         @include('partials.hero-slider', ['slides' => [...], 'autoplay' => 5000])

  Mỗi slide: image (URL đầy đủ hoặc đường dẫn trong public/), alt,
             eyebrow, title, desc, buttons => [[nhãn, url, 'ghost' (tuỳ chọn)], ...]
--}}
@php
  $autoplay = $autoplay ?? 6000;

  if (!isset($slides)) {
      try {
          $dbBanners = \Illuminate\Support\Facades\Schema::hasTable('banners')
              ? \App\Models\Banner::active()->get()
              : collect();

          if ($dbBanners->isNotEmpty()) {
              $slides = $dbBanners->map(function ($b) {
                  $buttons = [];
                  if (!empty($b->button_primary_label) && !empty($b->button_primary_url)) {
                      $buttons[] = [$b->button_primary_label, $b->button_primary_url];
                  }
                  if (!empty($b->button_secondary_label) && !empty($b->button_secondary_url)) {
                      $buttons[] = [$b->button_secondary_label, $b->button_secondary_url, 'ghost'];
                  }

                  return [
                      'image'   => $b->imageUrl,
                      'alt'     => $b->alt ?: $b->title,
                      'eyebrow' => $b->eyebrow,
                      'title'   => $b->title,
                      'desc'    => $b->desc,
                      'buttons' => $buttons,
                  ];
              })->toArray();
          }
      } catch (\Throwable) {
          // Fallback to default
      }
  }

  // Chưa có banner trong admin => banner mặc định theo ngôn ngữ đang chọn
  $slides = $slides ?? \App\Support\DefaultBanners::forSlider();

  // URL đầy đủ hoặc public storage thì giữ nguyên, còn lại coi là file trong public/
  $imgUrl = function ($p) {
    if (empty($p)) {
      return '';
    }
    if (\Illuminate\Support\Str::startsWith($p, ['http://', 'https://', '//', '/storage/'])) {
      return $p;
    }
    return asset($p);
  };
  $total  = count($slides);
@endphp

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/hero-slider.css') }}" />
@endpush

<section
  class="hs"
  tabindex="0"
  role="region"
  aria-roledescription="carousel"
  aria-label="Banner VIGER EDU"
  data-autoplay="{{ $autoplay }}"
>
  @foreach ($slides as $s)
    <article
      class="hs-slide {{ $loop->first ? 'is-active' : '' }}"
      role="group"
      aria-roledescription="slide"
      aria-label="{{ $loop->iteration }} / {{ $total }}"
      aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
    >
      <img
        src="{{ $imgUrl($s['image']) }}"
        alt="{{ $s['alt'] ?? '' }}"
        @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
      />
      <div class="hs-overlay"></div>

      <div class="hs-caption">
        @if (!empty($s['eyebrow']))
          <span class="hs-eyebrow">{{ $s['eyebrow'] }}</span>
        @endif
        <h2 class="hs-title">{{ $s['title'] }}</h2>
        @if (!empty($s['desc']))
          <p class="hs-desc">{{ $s['desc'] }}</p>
        @endif
        @if (!empty($s['buttons']))
          <div class="hs-actions">
            @foreach ($s['buttons'] as $b)
              <a href="{{ $b[1] }}" class="hs-btn {{ ($b[2] ?? '') === 'ghost' ? 'ghost' : '' }}">{{ $b[0] }}</a>
            @endforeach
          </div>
        @endif
      </div>
    </article>
  @endforeach

  <button type="button" class="hs-nav hs-prev" aria-label="Slide trước">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6" /></svg>
  </button>
  <button type="button" class="hs-nav hs-next" aria-label="Slide sau">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6" /></svg>
  </button>

  <div class="hs-dots">
    @foreach ($slides as $s)
      <button
        type="button"
        class="hs-dot {{ $loop->first ? 'is-active' : '' }}"
        aria-label="Chuyển tới slide {{ $loop->iteration }}"
        @if ($loop->first) aria-current="true" @endif
      ></button>
    @endforeach
  </div>

  <div class="hs-count" aria-hidden="true"><span class="cur">01</span> / {{ str_pad($total, 2, '0', STR_PAD_LEFT) }}</div>
  <div class="hs-progress" aria-hidden="true"><span></span></div>
</section>

@push('scripts')
  <script src="{{ asset('js/hero-slider.js') }}"></script>
@endpush
