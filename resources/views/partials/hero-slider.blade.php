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

  $slides = $slides ?? [
    [
      'image'   => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1920&q=80',
      'alt'     => 'Khuôn viên đại học tại Đức',
      'eyebrow' => 'Du học Đức',
      'title'   => 'Miễn học phí – Chất lượng quốc tế',
      'desc'    => 'Khám phá hệ thống giáo dục Đức: học tại các trường công lập uy tín với mức học phí bằng 0 dành cho sinh viên quốc tế.',
      'buttons' => [['Khám phá giáo dục Đức', '/giao-duc-tai-duc'], ['Nhận tư vấn miễn phí', '/lien-he', 'ghost']],
    ],
    [
      'image'   => 'https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=1920&q=80',
      'alt'     => 'Sinh viên trong khuôn viên trường',
      'eyebrow' => 'Lộ trình cá nhân hóa',
      'title'   => 'Từ chọn ngành đến ngày nhập học tại Đức',
      'desc'    => 'Đội ngũ chuyên gia từng học tập và làm việc tại Đức đồng hành cùng bạn trong từng bước của hành trình.',
      'buttons' => [['Xem quy trình tư vấn', '/tu-van/quy-trinh-tu-van'], ['Về chúng tôi', '/ve-chung-toi', 'ghost']],
    ],
    [
      'image'   => 'https://images.unsplash.com/photo-1607237138185-eedd9c632b0b?auto=format&fit=crop&w=1920&q=80',
      'alt'     => 'Thư viện trường đại học',
      'eyebrow' => 'Cơ hội nghề nghiệp',
      'title'   => 'Học tập hôm nay, làm việc tại Châu Âu ngày mai',
      'desc'    => 'Sinh viên quốc tế được ở lại đến 18 tháng sau tốt nghiệp để tìm việc tại nền kinh tế lớn nhất Châu Âu.',
      'buttons' => [['Cơ hội nghề nghiệp', '/giao-duc-tai-duc/co-hoi-nghe-nghiep']],
    ],
    [
      'image'   => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1920&q=80',
      'alt'     => 'Nhóm sinh viên cùng học tập',
      'eyebrow' => 'Đồng hành trọn gói',
      'title'   => 'Hồ sơ, visa, tài chính, nhà ở – chúng tôi lo cùng bạn',
      'desc'    => 'Hỗ trợ dịch thuật, APS, tài khoản phong tỏa, luyện phỏng vấn visa và đón tại sân bay Đức.',
      'buttons' => [['Dịch vụ tư vấn', '/tu-van'], ['Đặt lịch tư vấn', '/lien-he', 'ghost']],
    ],
    [
      'image'   => 'https://images.unsplash.com/photo-1599940824399-b87987ceb72a?auto=format&fit=crop&w=1920&q=80',
      'alt'     => 'Sinh viên quốc tế tại Đức',
      'eyebrow' => 'Thành công đã được chứng minh',
      'title'   => 'Hơn 500 học viên đã nhập học tại Đức',
      'desc'    => 'Tỷ lệ xin visa thành công 95% cùng hơn 10 năm kinh nghiệm đồng hành với học sinh, sinh viên Việt Nam.',
      'buttons' => [['Đọc kinh nghiệm từ blog', '/blog']],
    ],
  ];

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
