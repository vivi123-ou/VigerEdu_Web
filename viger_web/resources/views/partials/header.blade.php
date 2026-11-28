@php
  $siteName = \App\Models\Setting::get('site_name', 'VIGER.EDU.VN');
  $siteSlogan = \App\Models\Setting::get('site_slogan', 'From Vietnam to Europe');
  $siteLogo = \App\Models\Setting::get('site_logo', 'img/logo.png');
  // Ngôn ngữ chính (vi) lấy chữ từ Cài đặt trong admin; ngôn ngữ khác lấy từ resources/lang
  $headerCtaText = app()->isLocale(config('app.supported_locales')[0])
    ? \App\Models\Setting::get('header_cta_text', __('messages.start_now'))
    : __('messages.start_now');
  $headerCtaUrl = \App\Models\Setting::get('header_cta_url', '/lien-he');

  try {
    $hasCatTable = \Illuminate\Support\Facades\Schema::hasTable('categories');
    $eduCategories = $hasCatTable ? \App\Models\Category::inMenu('giao-duc-tai-duc')->with('englishVersion')->get() : collect();
    $consultCategories = $hasCatTable ? \App\Models\Category::inMenu('tu-van')->with('englishVersion')->get() : collect();
    $blogCategories = $hasCatTable ? \App\Models\Category::inMenu('blog')->with('englishVersion')->get() : collect();
    $mainMenuItems = $hasCatTable
      ? \App\Models\Category::inMenu('main')->whereNull('parent_id')->with(['englishVersion', 'children.englishVersion'])->get()
      : collect();
  } catch (\Throwable) {
    $eduCategories = collect();
    $consultCategories = collect();
    $blogCategories = collect();
    $mainMenuItems = collect();
  }
@endphp

<header class="bar">
  <div class="brand-group">
    <a class="brand" href="{{ localized_url('/') }}">
      <img src="{{ \Illuminate\Support\Str::startsWith($siteLogo, ['http', '/']) ? $siteLogo : asset($siteLogo) }}" alt="Logo" />
      <div
        class="brand-text"
        style="display: flex; flex-direction: column; line-height: 1.2"
      >
        <span id="brand-title">{{ $siteName }}</span>
        <span id="brand-sub">{{ $siteSlogan }}</span>
      </div>
    </a>
  </div>

  <input class="navtoggle" type="checkbox" id="nav-open" />
  <label class="scrim" for="nav-open" aria-hidden="true"></label>
  <label class="burger" for="nav-open" aria-label="{{ __('messages.menu') }}">
    <svg viewBox="0 0 22 14">
      <path class="b1" d="M1 1 H21" />
      <path class="b2" d="M1 7 H21" />
      <path class="b3" d="M1 13 H21" />
    </svg>
  </label>

  <nav class="navpanel menu">
    <a href="{{ localized_url('/ve-chung-toi') }}"><span id="nav-home">{{ __('messages.about_us') }}</span></a>

    <div class="menu-dropdown">
      <a href="{{ localized_url('/giao-duc-tai-duc') }}"
        ><span id="nav-about">{{ __('messages.education') }}</span>
        <svg class="caret" viewBox="0 0 9 6" aria-hidden="true">
          <path d="M0.7 1.1 L4.5 4.6 L8.3 1.1" />
        </svg>
      </a>
      <div class="dropdown-content">
        @if ($eduCategories->isNotEmpty())
          @foreach ($eduCategories as $c)
            <a href="{{ $c->menuUrl() }}">{{ $c->displayName() }}</a>
          @endforeach
        @else
          <a href="{{ localized_url('/giao-duc-tai-duc/tong-quan') }}">{{ __('messages.overview') }}</a>
          <a href="{{ localized_url('/giao-duc-tai-duc/he-thong-giao-duc') }}">{{ __('messages.education_system') }}</a>
          <a href="{{ localized_url('/giao-duc-tai-duc/nghien-cuu-va-ung-dung') }}">{{ __('messages.research_applied') }}</a>
          <a href="{{ localized_url('/giao-duc-tai-duc/chi-phi-va-co-hoi') }}">{{ __('messages.costs_opportunities') }}</a>
          <a href="{{ localized_url('/giao-duc-tai-duc/co-hoi-nghe-nghiep') }}">{{ __('messages.career_opportunities') }}</a>
        @endif
      </div>
    </div>

    <div class="menu-dropdown">
      <a href="{{ localized_url('/tu-van') }}"
        ><span id="nav-consult">{{ __('messages.consulting') }}</span>
        <svg class="caret" viewBox="0 0 9 6" aria-hidden="true">
          <path d="M0.7 1.1 L4.5 4.6 L8.3 1.1" />
        </svg>
      </a>
      <div class="dropdown-content">
        @if ($consultCategories->isNotEmpty())
          @foreach ($consultCategories as $c)
            <a href="{{ $c->menuUrl() }}">{{ $c->displayName() }}</a>
          @endforeach
          <a href="{{ localized_url('/tu-van/quy-trinh-tu-van') }}">{{ __('messages.consult_process') }}</a>
        @else
          <a href="{{ localized_url('/tu-van/du-hoc-dai-hoc') }}">{{ __('messages.consult_bachelor') }}</a>
          <a href="{{ localized_url('/tu-van/du-hoc-cao-hoc') }}">{{ __('messages.consult_master') }}</a>
          <a href="{{ localized_url('/tu-van/quy-trinh-tu-van') }}">{{ __('messages.consult_process') }}</a>
        @endif
      </div>
    </div>

    <a href="{{ localized_url('/chuong-trinh-hoc') }}"
      ><span id="nav-programs">{{ __('messages.programs') }}</span></a
    >

    <div class="menu-dropdown">
      <a href="{{ localized_url('/blog') }}"
        ><span id="nav-blog">{{ __('messages.blog') }}</span>
        <svg class="caret" viewBox="0 0 9 6" aria-hidden="true">
          <path d="M0.7 1.1 L4.5 4.6 L8.3 1.1" />
        </svg>
      </a>
      <div class="dropdown-content">
        @if ($blogCategories->isNotEmpty())
          @foreach ($blogCategories as $c)
            <a href="{{ $c->menuUrl() }}">{{ $c->displayName() }}</a>
          @endforeach
        @else
          <a href="{{ localized_url('/blog/hieu-ve-duc') }}">{{ __('messages.understand_germany') }}</a>
          <a href="{{ localized_url('/blog/cuoc-song-o-duc') }}">{{ __('messages.life_in_germany') }}</a>
        @endif
      </div>
    </div>

    {{-- Menu chính quản trị từ Filament: mục có mục con sẽ hiển thị dạng dropdown --}}
    @foreach ($mainMenuItems as $item)
      @if ($item->children->isNotEmpty())
        <div class="menu-dropdown">
          <a href="{{ $item->menuUrl() }}"
            ><span>{{ $item->displayName() }}</span>
            <svg class="caret" viewBox="0 0 9 6" aria-hidden="true">
              <path d="M0.7 1.1 L4.5 4.6 L8.3 1.1" />
            </svg>
          </a>
          <div class="dropdown-content">
            @foreach ($item->children as $child)
              <a href="{{ $child->menuUrl() }}">{{ $child->displayName() }}</a>
            @endforeach
          </div>
        </div>
      @else
        <a href="{{ $item->menuUrl() }}"><span>{{ $item->displayName() }}</span></a>
      @endif
    @endforeach

    {{-- Tương đương sec:authorize="hasRole('ADMIN')" --}}
    @auth
      @if (auth()->user()->role === 'ADMIN')
        <div class="menu-dropdown">
          <a href="/admin"
            ><span id="nav-admin">{{ __('messages.admin') }}</span>
            <svg class="caret" viewBox="0 0 9 6" aria-hidden="true">
              <path d="M0.7 1.1 L4.5 4.6 L8.3 1.1" />
            </svg>
          </a>
          <div class="dropdown-content">
            <form
              action="{{ url('/logout') }}"
              method="post"
              id="logoutForm"
              style="display: none"
            >
              @csrf
            </form>
            <button type="submit" form="logoutForm" class="logout-link">
              {{ __('messages.logout') }}
            </button>
          </div>
        </div>
      @endif
    @endauth

  </nav>

  <div class="header-actions">
    {{-- Nút chuyển ngôn ngữ (đặt ngoài <nav> để vẫn hiện trên mobile) --}}
    @php
      $currentLocale = app()->getLocale();
      $nextLocale = collect(config('app.supported_locales'))->first(fn ($locale) => $locale !== $currentLocale);
    @endphp
    <a
      class="lang-toggle"
      href="{{ \App\Support\LocalizedUrl::switchTo($nextLocale) }}"
      hreflang="{{ $nextLocale }}"
      title="{{ __('messages.switch_lang') }}"
      aria-label="{{ __('messages.switch_lang') }}"
    >
      @foreach (config('app.supported_locales') as $locale)
        @if (! $loop->first)<span class="lang-sep">|</span>@endif
        <span @class(['lang-active' => $locale === $currentLocale])>{{ strtoupper($locale) }}</span>
      @endforeach
    </a>

    <a class="cta-pill" href="{{ localized_url($headerCtaUrl) }}"
      ><span id="nav-cta">{{ $headerCtaText }}</span></a
    >
  </div>
</header>
