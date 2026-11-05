@php
  $siteName = \App\Models\Setting::get('site_name', 'VIGER.EDU.VN');
  $siteSlogan = \App\Models\Setting::get('site_slogan', 'From Vietnam to Europe');
  $siteLogo = \App\Models\Setting::get('site_logo', 'img/logo.png');
  $footerDesc = \App\Models\Setting::get('footer_description', 'Đồng hành cùng người trẻ Việt Nam trên hành trình chinh phục giáo dục Đức - miễn học phí, chất lượng quốc tế.');
  $facebookUrl = \App\Models\Setting::get('social_facebook', 'https://www.facebook.com/duhocVIGER');
  $youtubeUrl = \App\Models\Setting::get('social_youtube', 'https://www.youtube.com/@SPN.Invest');
  $tiktokUrl = \App\Models\Setting::get('social_tiktok', 'https://www.tiktok.com/@spn.invest');
  $contactEmail = \App\Models\Setting::get('contact_email', 'info@spn.com.vn');
  $copyright = \App\Models\Setting::get('footer_copyright', 'Copyright © ' . date('Y') . ' VIGER.EDU.VN. All rights reserved.');
@endphp

<footer class="foot">
  <div class="foot-container">
    <!-- Cột trái: Logo + Text + Socials -->
    <div class="foot-left animate-hidden">
      <a class="brand foot-brand" href="/">
        <img src="{{ \Illuminate\Support\Str::startsWith($siteLogo, ['http', '/']) ? $siteLogo : asset($siteLogo) }}" alt="Logo" />
        <div
          class="brand-text"
          style="display: flex; flex-direction: column; line-height: 1.2"
        >
          <span id="brand-title">{{ $siteName }}</span>
          <span id="brand-sub">{{ $siteSlogan }}</span>
        </div>
      </a>
      <p class="foot-desc">
        {{ $footerDesc }}
      </p>
      <div class="foot-socials">
        @if ($facebookUrl)
          <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
              <path
                d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"
              />
            </svg>
          </a>
        @endif

        @if ($youtubeUrl)
          <a href="{{ $youtubeUrl }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
              <path
                d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.5 12 3.5 12 3.5s-7.505 0-9.377.55a3.016 3.016 0 0 0-2.122 2.136C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.55 9.376.55 9.376.55s7.505 0 9.377-.55a3.016 3.016 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"
              />
            </svg>
          </a>
        @endif

        @if ($tiktokUrl)
          <a href="{{ $tiktokUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Tiktok">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
              <path
                d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"
              />
            </svg>
          </a>
        @endif

        @if ($contactEmail)
          <a href="mailto:{{ $contactEmail }}" aria-label="Gmail">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
              <path
                d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.91 1.528-1.145C21.69 2.28 24 3.434 24 5.457z"
              />
            </svg>
          </a>
        @endif
      </div>
    </div>

    <!-- Cột phải: Links -->
    <div class="foot-right animate-hidden delay-100">
      <nav class="foot-nav">
        <a href="/ve-chung-toi">Về chúng tôi</a>
        <a href="/giao-duc-tai-duc">Giáo dục tại Đức</a>
        <a href="/tu-van">Tư vấn</a>
        <a href="/chuong-trinh-hoc">Chương trình học</a>
        <a href="/blog">Blog</a>
      </nav>
    </div>

    <div class="foot-copy animate-hidden delay-200">
      {{ $copyright }}
    </div>
  </div>
</footer>
