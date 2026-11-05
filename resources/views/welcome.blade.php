@extends('layouts.app')

@section('title', 'Trang chủ')

@push('styles')
  <style>
    .mouse-glow {
  --glow-color: 193 18 31;
  --glow-size: 700px;
  --glow-opacity: 0.14;
  position: relative;
  isolation: isolate;
}
.mouse-glow::before {
  content: "";
  pointer-events: none;
  position: fixed;
  inset: 0;
  z-index: 5; /* nằm TRÊN nội dung để thấy được cả trên nền hero tối */
  background: var(--_glow-pos, none);
  will-change: background;
}
.mouse-glow > * {
  position: relative;
  z-index: 1;
}
 
/* ── 2. Con trỏ tuỳ chỉnh: chấm + vòng đuổi theo ───────────────── */
html.fx-on,
html.fx-on * {
  cursor: none !important;
}
html.fx-on input,
html.fx-on textarea,
html.fx-on select,
html.fx-on [contenteditable="true"] {
  cursor: auto !important;
}
 
.fx-cursor-dot,
.fx-cursor-ring {
  position: fixed;
  left: 0;
  top: 0;
  pointer-events: none;
  z-index: 100000;
  border-radius: 50%;
  opacity: 0;
  will-change: transform;
  transition: opacity 0.3s ease;
}
html.fx-seen .fx-cursor-dot,
html.fx-seen .fx-cursor-ring {
  opacity: 1;
}
html.fx-text .fx-cursor-dot,
html.fx-text .fx-cursor-ring {
  opacity: 0; /* ô nhập liệu: trả lại con trỏ gõ chữ của hệ thống */
}
 
.fx-cursor-dot {
  width: 8px;
  height: 8px;
  margin: -4px 0 0 -4px;
  background: var(--accent-main, #c1121f);
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.85);
}
.fx-cursor-ring {
  width: 38px;
  height: 38px;
  margin: -19px 0 0 -19px;
  border: 1.5px solid rgba(193, 18, 31, 0.65);
  box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.45), 0 0 18px rgba(193, 18, 31, 0.25);
  transition:
    opacity 0.3s ease,
    width 0.28s cubic-bezier(0.34, 1.56, 0.64, 1),
    height 0.28s cubic-bezier(0.34, 1.56, 0.64, 1),
    margin 0.28s cubic-bezier(0.34, 1.56, 0.64, 1),
    background 0.25s ease,
    border-color 0.25s ease;
}
/* Rê vào link/nút: vòng phình to, nền mờ */
.fx-cursor-ring.fx-hover {
  width: 68px;
  height: 68px;
  margin: -34px 0 0 -34px;
  background: rgba(193, 18, 31, 0.14);
  border-color: var(--accent-alt, #ffb703);
}
/* Rê vào thẻ nghiêng 3D */
.fx-cursor-ring.fx-card {
  width: 54px;
  height: 54px;
  margin: -27px 0 0 -27px;
  border-color: var(--accent-alt, #ffb703);
}
/* Đang nhấn chuột */
.fx-cursor-ring.fx-down {
  width: 26px;
  height: 26px;
  margin: -13px 0 0 -13px;
  background: rgba(193, 18, 31, 0.3);
}
 
/* ── 3. Gợn sóng khi bấm ──────────────────────────────────────── */
.fx-ripple {
  position: fixed;
  width: 14px;
  height: 14px;
  margin: -7px 0 0 -7px;
  border-radius: 50%;
  border: 2px solid var(--accent-alt, #ffb703);
  pointer-events: none;
  z-index: 99999;
  animation: fx-ripple 0.7s cubic-bezier(0.2, 0.7, 0.2, 1) forwards;
}
@keyframes fx-ripple {
  from { transform: scale(1); opacity: 0.9; }
  to   { transform: scale(11); opacity: 0; }
}
 
/* ── 4. Canvas hạt sáng bám theo chuột ────────────────────────── */
.fx-canvas {
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 99998;
}
 
/* ── 5. Thẻ nghiêng 3D + đốm sáng bên trong thẻ ───────────────── */
.fx-tilt {
  transform-style: preserve-3d;
  will-change: transform;
}
.fx-spot {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  pointer-events: none;
  opacity: 0;
  z-index: 2;
  transition: opacity 0.3s ease;
  background: radial-gradient(
    280px circle at var(--mx, 50%) var(--my, 50%),
    rgb(var(--fx-spot-rgb, 193 18 31) / 0.16),
    transparent 65%
  );
}
.fx-tilt:hover > .fx-spot {
  opacity: 1;
}
/* Phần tử con "nổi" lên khỏi mặt thẻ khi nghiêng → cảm giác chiều sâu */
.fx-tilt .circle-step,
.fx-tilt .author-box {
  transition: transform 0.35s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.fx-tilt.fx-tilting .circle-step {
  transform: translateZ(38px) scale(1.08);
}
.fx-tilt.fx-tilting .author-box {
  transform: translateZ(26px);
}
 
/* ── 6. Nút "từ tính" ─────────────────────────────────────────── */
.fx-magnetic {
  will-change: translate;
}
 
/* ── 7. Hero: orb sáng trôi theo chuột (hiệu ứng chiều sâu) ───── */
.hero-section {
  position: relative;
}
.hero-section > .hero-container {
  position: relative;
  z-index: 1;
}
.fx-orbs {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  z-index: 0;
}
.fx-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  opacity: 0.55;
  will-change: translate;
}
.fx-orb.o1 {
  width: 340px; height: 340px; left: -80px; top: 10%;
  background: radial-gradient(circle at 30% 30%, rgba(193, 18, 31, 0.9), transparent 70%);
}
.fx-orb.o2 {
  width: 280px; height: 280px; right: 8%; top: -60px;
  background: radial-gradient(circle, rgba(255, 183, 3, 0.75), transparent 70%);
}
.fx-orb.o3 {
  width: 260px; height: 260px; right: 30%; bottom: -80px;
  background: radial-gradient(circle, rgba(66, 180, 146, 0.6), transparent 70%);
}
 
  </style>
@endpush

@use('App\Support\HomePage')

@php
  // Nội dung lấy từ admin (Filament > Trang chủ), rỗng thì dùng mặc định
  $h = HomePage::all();
@endphp

@section('content')
  <div class="mouse-glow" data-glow-speed="0.07">
  <main class="main-content">
    <!-- 1. HERO SECTION -->
    <section
      class="hero-section"
      style="
        background:
          linear-gradient(rgba(18, 38, 63, 0.85), rgba(18, 38, 63, 0.9)),
          url('{{ HomePage::image('home_hero_background') }}')
            no-repeat center center;
        background-size: cover;
        color: white;
      "
    >
      <div class="hero-container">
        <div class="hero-grid">
          <div class="hero-text animate-hidden">
            <h1 class="hero-heading" style="color: white">
              <span>{{ $h['home_hero_heading'] }}</span>
            </h1>
            <h1 class="hero-subheading" style="color: var(--accent-alt)">
              <span>{{ $h['home_hero_subheading'] }}</span>
            </h1>
            <p class="hero-desc" style="color: rgba(255, 255, 255, 0.9)">
              <span>{{ $h['home_hero_description'] }}</span>
            </p>
            <div class="hero-stats" id="counter-section">
              @foreach ($h['home_hero_stats'] as $stat)
                <div class="hero-stat">
                  <p class="stat-number">
                    <span class="counter-value" data-target="{{ (int) ($stat['number'] ?? 0) }}">0</span>{{ $stat['suffix'] ?? '' }}
                  </p>
                  <p class="stat-label">{{ $stat['label'] ?? '' }}</p>
                </div>
              @endforeach
            </div>
            <div class="hero-buttons">
              <a href="{{ $h['home_hero_button_url'] }}"
                ><button class="btn-red">
                  {{ $h['home_hero_button_text'] }}
                </button></a
              >
              <a
                href="#"
                id="btn-roadmap"
                class="btn-outline-custom"
                >{{ $h['home_roadmap_button_text'] }}</a
              >
            </div>
          </div>
          <div class="hero-image animate-hidden delay-200">
            <img src="{{ HomePage::image('home_hero_image') }}" alt="{{ $h['home_hero_heading'] }}" />
          </div>
        </div>
      </div>
    </section>
    

    <!-- 2. PARTNER BANNER -->
    <div class="yellow-banner">
      <div class="partner-list">
        @foreach ([1, 2] as $copy)
          @foreach ($h['home_partners'] as $partner)
            <span @if ($copy === 2) aria-hidden="true" @endif>{{ is_array($partner) ? ($partner['name'] ?? '') : $partner }}</span>
          @endforeach
        @endforeach
      </div>
    </div>

     @include('partials.hero-slider')

    <!-- 3. ABOUT SECTION -->
    <section class="about-section">
      <div class="page-container">
        <div class="about-grid">
          <div class="about-text animate-hidden">
            <div class="about-label">
              <span class="about-label-text">{{ $h['home_about_label'] }}</span>
            </div>
            <h2 class="about-heading">
              <span>{{ $h['home_about_heading'] }}</span>
            </h2>
            <p class="about-desc">
              <span>{{ $h['home_about_description'] }}</span>
            </p>
            <a href="{{ $h['home_about_link_url'] }}" class="link-custom">{{ $h['home_about_link_text'] }}</a>
          </div>
          <div class="about-images animate-hidden delay-200">
            <div class="grid-images">
              <img
                src="{{ HomePage::image('home_about_image_1') }}"
                class="grid-img"
                alt="Trường Đức 1"
              />
              <img
                src="{{ HomePage::image('home_about_image_2') }}"
                class="grid-img"
                alt="Trường Đức 2"
              />
              <div class="stat-box">
                <h3>{{ $h['home_about_stat_number'] }}</h3>
                <p>{{ $h['home_about_stat_label'] }}</p>
              </div>
              <img
                src="{{ HomePage::image('home_about_image_3') }}"
                class="grid-img"
                alt="Khuôn viên trường"
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 4. PROCESS SECTION -->
    <section class="process-section">
      <div class="page-container">
        <div class="text-center animate-hidden">
          <div class="section-pretitle" style="margin: 0 auto 1rem">
            <span>{{ $h['home_process_pretitle'] }}</span>
          </div>
          <h2 class="section-title">{{ $h['home_process_title'] }}</h2>
        </div>
        <div class="timeline-wrap">
          <div class="timeline-grid">
            @foreach (array_values($h['home_process_steps']) as $i => $step)
              <div class="timeline-step animate-hidden delay-{{ min(($i + 1) * 100, 500) }}">
                <div class="circle-step">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
                <h4 class="step-title">{{ $step['title'] ?? '' }}</h4>
                <p class="step-desc">{{ $step['description'] ?? '' }}</p>
              </div>
            @endforeach
          </div>
        </div>
        <div class="text-center animate-hidden">
          <a href="{{ $h['home_process_link_url'] }}" class="link-more">{{ $h['home_process_link_text'] }}</a>
        </div>
      </div>
    </section>

    <!-- 5. TESTIMONIALS -->
    <section class="testimonial-section">
      <div class="page-container">
        <h2 class="section-title animate-hidden">{{ $h['home_testimonial_title'] }}</h2>
        <div class="testimonial-grid">
          @foreach ($h['home_testimonials'] as $t)
            @php
              $avatar = HomePage::imageUrl($t['avatar'] ?? null, $t['default_avatar'] ?? null);
              $initial = mb_strtoupper(mb_substr(trim($t['name'] ?? '?'), 0, 1));
            @endphp
            <div class="testimonial-item animate-hidden delay-100">
              <div class="testimonial-card">
                <p class="testimonial-text">{{ $t['content'] ?? '' }}</p>
                <div class="author-box">
                  @if ($avatar)
                    <img
                      src="{{ $avatar }}"
                      alt="{{ $t['name'] ?? '' }}"
                      style="width: 36px; height: 36px; border-radius: var(--radius-full); object-fit: cover"
                    />
                  @else
                    <span style="width: 36px; height: 36px; border-radius: var(--radius-full); background: var(--accent-main); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700">{{ $initial }}</span>
                  @endif
                  <span>{{ $t['name'] ?? '' }}</span>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <!-- 6. CTA SECTION -->
    <section class="cta-section animate-hidden">
      <div class="page-container">
        <div class="section-pretitle" style="justify-content: center">
          <span>{{ $h['home_cta_pretitle'] }}</span>
        </div>
        <h2 class="cta-title">{{ $h['home_cta_title'] }}</h2>
        <p class="cta-desc">{{ $h['home_cta_description'] }}</p>
        <a class="cta-pill" href="{{ $h['home_cta_button_url'] }}">{{ $h['home_cta_button_text'] }}</a>
      </div>
    </section>
  </main>
  </div>{{-- /.mouse-glow --}}

  <!-- Modal lộ trình du học -->
  <style>
    @keyframes modalFadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes modalFadeOut {
      from { opacity: 1; }
      to { opacity: 0; }
    }
    @keyframes modalSlideIn {
      from { opacity: 0; transform: scale(0.88) translateY(24px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
    }
    @keyframes modalSlideOut {
      from { opacity: 1; transform: scale(1) translateY(0); }
      to { opacity: 0; transform: scale(0.88) translateY(24px); }
    }
    #roadmap-modal {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 9999;
      background: rgba(0, 0, 0, 0.75);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }
    #roadmap-modal.is-opening {
      display: flex;
      animation: modalFadeIn 0.25s ease forwards;
    }
    #roadmap-modal.is-open {
      display: flex;
    }
    #roadmap-modal.is-closing {
      display: flex;
      animation: modalFadeOut 0.22s ease forwards;
    }
    #roadmap-modal-inner {
      position: relative;
      max-width: 900px;
      width: 100%;
    }
    #roadmap-modal.is-opening #roadmap-modal-inner {
      animation: modalSlideIn 0.32s cubic-bezier(0.34, 1.3, 0.64, 1) forwards;
    }
    #roadmap-modal.is-closing #roadmap-modal-inner {
      animation: modalSlideOut 0.22s ease forwards;
    }
    #roadmap-modal-close {
      position: absolute;
      top: -2.75rem;
      right: 0;
      background: rgba(255, 255, 255, 0.12);
      border: 1.5px solid rgba(255, 255, 255, 0.3);
      color: #fff;
      font-size: 1.5rem;
      width: 2.25rem;
      height: 2.25rem;
      border-radius: 50%;
      cursor: pointer;
      line-height: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s, transform 0.2s;
    }
    #roadmap-modal-close:hover {
      background: rgba(255, 255, 255, 0.25);
      transform: rotate(90deg) scale(1.1);
    }
    #roadmap-modal img {
      width: 100%;
      border-radius: 1rem;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.55);
      display: block;
    }
  </style>

  <div id="roadmap-modal">
    <div id="roadmap-modal-inner">
      <button id="roadmap-modal-close" aria-label="Đóng">&times;</button>
      <img src="{{ HomePage::image('home_roadmap_image') }}" alt="Lộ trình tư vấn" />
    </div>
  </div>
@endsection

@push('scripts')
  <script src="{{ asset('js/counters.js') }}"></script>
  <script src="{{ asset('js/animations.js') }}"></script>
  {{-- <script src="{{ asset('js/mouse-glow.js') }}"></script> --}}
  <script>
    (function () {
      var modal = document.getElementById("roadmap-modal");
      var btnOpen = document.getElementById("btn-roadmap");
      var btnClose = document.getElementById("roadmap-modal-close");
      var ANIM_MS = 220; // must match CSS animation duration

      function openModal(e) {
        e.preventDefault();
        modal.classList.remove("is-closing", "is-open");
        modal.classList.add("is-opening");
        document.body.style.overflow = "hidden";
        setTimeout(function () {
          modal.classList.remove("is-opening");
          modal.classList.add("is-open");
        }, 320);
      }

      function closeModal() {
        modal.classList.remove("is-opening", "is-open");
        modal.classList.add("is-closing");
        setTimeout(function () {
          modal.classList.remove("is-closing");
          modal.style.display = "none";
          // Reset display so CSS 'is-opening' can set flex again
          modal.style.display = "";
          document.body.style.overflow = "";
        }, ANIM_MS);
      }

      btnOpen.addEventListener("click", openModal);
      btnClose.addEventListener("click", closeModal);
      modal.addEventListener("click", function (e) {
        if (e.target === modal) closeModal();
      });
      document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && modal.classList.contains("is-open"))
          closeModal();
      });
    })();
  </script>
@endpush