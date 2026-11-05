@php
  $siteName = \App\Models\Setting::get('site_name', 'VIGER.EDU.VN');
  $siteSlogan = \App\Models\Setting::get('site_slogan', 'From Vietnam to Europe');
  $siteLogo = \App\Models\Setting::get('site_logo', 'img/logo.png');
  $headerCtaText = \App\Models\Setting::get('header_cta_text', 'Bắt đầu ngay');
  $headerCtaUrl = \App\Models\Setting::get('header_cta_url', '/lien-he');

  try {
    $hasCatTable = \Illuminate\Support\Facades\Schema::hasTable('categories');
    $eduCategories = $hasCatTable ? \App\Models\Category::inMenu('giao-duc-tai-duc')->get() : collect();
    $consultCategories = $hasCatTable ? \App\Models\Category::inMenu('tu-van')->get() : collect();
    $blogCategories = $hasCatTable ? \App\Models\Category::inMenu('blog')->get() : collect();
    $mainMenuItems = $hasCatTable
      ? \App\Models\Category::inMenu('main')->whereNull('parent_id')->with('children')->get()
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
    <a class="brand" href="/">
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
  <label class="burger" for="nav-open" aria-label="Menu">
    <svg viewBox="0 0 22 14">
      <path class="b1" d="M1 1 H21" />
      <path class="b2" d="M1 7 H21" />
      <path class="b3" d="M1 13 H21" />
    </svg>
  </label>

  <nav class="navpanel menu">
    <a href="/ve-chung-toi"><span id="nav-home">Về chúng tôi</span></a>

    <div class="menu-dropdown">
      <a href="/giao-duc-tai-duc"
        ><span id="nav-about">Giáo dục tại Đức</span>
        <svg class="caret" viewBox="0 0 9 6" aria-hidden="true">
          <path d="M0.7 1.1 L4.5 4.6 L8.3 1.1" />
        </svg>
      </a>
      <div class="dropdown-content">
        @if ($eduCategories->isNotEmpty())
          @foreach ($eduCategories as $c)
            <a href="{{ $c->menuUrl() }}">{{ $c->name }}</a>
          @endforeach
        @else
          <a href="/giao-duc-tai-duc/tong-quan">Tổng quan</a>
          <a href="/giao-duc-tai-duc/he-thong-giao-duc">Hệ thống giáo dục</a>
          <a href="/giao-duc-tai-duc/nghien-cuu-va-ung-dung">Nghiên cứu và Ứng dụng</a>
          <a href="/giao-duc-tai-duc/chi-phi-va-co-hoi">Chi phí và Cơ hội</a>
          <a href="/giao-duc-tai-duc/co-hoi-nghe-nghiep">Cơ hội nghề nghiệp</a>
        @endif
      </div>
    </div>

    <div class="menu-dropdown">
      <a href="/tu-van"
        ><span id="nav-consult">Tư vấn</span>
        <svg class="caret" viewBox="0 0 9 6" aria-hidden="true">
          <path d="M0.7 1.1 L4.5 4.6 L8.3 1.1" />
        </svg>
      </a>
      <div class="dropdown-content">
        @if ($consultCategories->isNotEmpty())
          @foreach ($consultCategories as $c)
            <a href="{{ $c->menuUrl() }}">{{ $c->name }}</a>
          @endforeach
          <a href="/tu-van/quy-trinh-tu-van">Quy trình Tư vấn</a>
        @else
          <a href="/tu-van/du-hoc-dai-hoc">Du học Đại học</a>
          <a href="/tu-van/du-hoc-cao-hoc">Du học Cao học</a>
          <a href="/tu-van/quy-trinh-tu-van">Quy trình Tư vấn</a>
        @endif
      </div>
    </div>

    <a href="/chuong-trinh-hoc"
      ><span id="nav-programs">Chương trình học</span></a
    >

    <div class="menu-dropdown">
      <a href="/blog"
        ><span id="nav-blog">Blog</span>
        <svg class="caret" viewBox="0 0 9 6" aria-hidden="true">
          <path d="M0.7 1.1 L4.5 4.6 L8.3 1.1" />
        </svg>
      </a>
      <div class="dropdown-content">
        @if ($blogCategories->isNotEmpty())
          @foreach ($blogCategories as $c)
            <a href="{{ $c->menuUrl() }}">{{ $c->name }}</a>
          @endforeach
        @else
          <a href="/blog/hieu-ve-duc">Hiểu về Đức</a>
          <a href="/blog/cuoc-song-o-duc">Cuộc sống ở Đức</a>
        @endif
      </div>
    </div>

    {{-- Menu chính quản trị từ Filament: mục có mục con sẽ hiển thị dạng dropdown --}}
    @foreach ($mainMenuItems as $item)
      @if ($item->children->isNotEmpty())
        <div class="menu-dropdown">
          <a href="{{ $item->menuUrl() }}"
            ><span>{{ $item->name }}</span>
            <svg class="caret" viewBox="0 0 9 6" aria-hidden="true">
              <path d="M0.7 1.1 L4.5 4.6 L8.3 1.1" />
            </svg>
          </a>
          <div class="dropdown-content">
            @foreach ($item->children as $child)
              <a href="{{ $child->menuUrl() }}">{{ $child->name }}</a>
            @endforeach
          </div>
        </div>
      @else
        <a href="{{ $item->menuUrl() }}"><span>{{ $item->name }}</span></a>
      @endif
    @endforeach

    {{-- Tương đương sec:authorize="hasRole('ADMIN')" --}}
    @auth
      @if (auth()->user()->role === 'ADMIN')
        <div class="menu-dropdown">
          <a href="/admin"
            ><span id="nav-admin">Phần quản trị</span>
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
              Đăng xuất
            </button>
          </div>
        </div>
      @endif
    @endauth
  </nav>

  <a class="cta-pill" href="{{ $headerCtaUrl }}"
    ><span id="nav-cta">{{ $headerCtaText }}</span></a
  >
</header>
