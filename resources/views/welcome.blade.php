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

@section('content')
  <div class="mouse-glow" data-glow-speed="0.07">
  <main class="main-content">
    <!-- 1. HERO SECTION -->
    <section
      class="hero-section"
      style="
        background:
          linear-gradient(rgba(18, 38, 63, 0.85), rgba(18, 38, 63, 0.9)),
          url('https://images.unsplash.com/photo-1618259278412-2819cbdea4dc?q=80&w=1121&auto=format&fit=crop')
            no-repeat center center;
        background-size: cover;
        color: white;
      "
    >
      <div class="hero-container">
        <div class="hero-grid">
          <div class="hero-text animate-hidden">
            <h1 class="hero-heading" style="color: white">
              <span data-i18n="idx-hero-heading">Hành trình của bạn</span>
            </h1>
            <h1 class="hero-subheading" style="color: var(--accent-alt)">
              <span data-i18n="idx-hero-subheading">Bắt đầu từ nước Đức...</span>
            </h1>
            <p class="hero-desc" style="color: rgba(255, 255, 255, 0.9)">
              <span data-i18n="idx-hero-desc"
                >Viger EDU đồng hành cùng bạn từ lựa chọn ngành học, chuẩn bị hồ
                sơ đến khi đặt chân đến giảng đường Đức — với đội ngũ chuyên gia
                đã từng học tập và làm việc tại đây.</span
              >
            </p>
            <div class="hero-stats" id="counter-section">
              <div class="hero-stat">
                <p class="stat-number">
                  <span class="counter-value" data-target="500">0</span>+
                </p>
                <p class="stat-label" data-i18n="idx-stat-students">Học viên nhập học</p>
              </div>
              <div class="hero-stat">
                <p class="stat-number">
                  <span class="counter-value" data-target="95">0</span>%
                </p>
                <p class="stat-label" data-i18n="idx-stat-visa">Xin Visa thành công</p>
              </div>
              <div class="hero-stat">
                <p class="stat-number">
                  <span class="counter-value" data-target="10">0</span>+
                </p>
                <p class="stat-label" data-i18n="idx-stat-years">Năm kinh nghiệm</p>
              </div>
            </div>
            <div class="hero-buttons">
              <a href="/lien-he"
                ><button class="btn-red" data-i18n="idx-btn-consult">
                  Đặt lịch tư vấn miễn phí
                </button></a
              >
              <a
                href="#"
                id="btn-roadmap"
                class="btn-outline-custom"
                data-i18n="idx-btn-roadmap"
                >Xem lộ trình du học</a
              >
            </div>
          </div>
          <div class="hero-image animate-hidden delay-200">
            <img src="{{ asset('img/trangchu.png') }}" alt="Du học sinh Đức" />
          </div>
        </div>
      </div>
    </section>

    <!-- 2. PARTNER BANNER -->
    <div class="yellow-banner">
      <div class="partner-list">
        <span>Technical University of Munich (TUM)</span>
        <span>Ludwig-Maximilians-Universität München (LMU)</span>
        <span>Ruprecht-Karls-Universität Heidelberg</span>
        <span>Freie Universität Berlin</span>
        <span>Technical University of Munich (TUM)</span>
        <span>Ludwig-Maximilians-Universität München (LMU)</span>
      </div>
    </div>

    <!-- 3. ABOUT SECTION -->
    <section class="about-section">
      <div class="page-container">
        <div class="about-grid">
          <div class="about-text animate-hidden">
            <div class="about-label">
              <span class="about-label-text" data-i18n="idx-about-label"
                >✨ Về chúng tôi</span
              >
            </div>
            <h2 class="about-heading">
              <span data-i18n="idx-about-heading"
                >Người bạn đồng hành đáng tin cậy nhất trên con đường đến Đức</span
              >
            </h2>
            <p class="about-desc">
              <span data-i18n="idx-about-desc"
                >Chúng tôi không chỉ tư vấn — chúng tôi đồng hành. Đội ngũ Viger
                EDU gồm các chuyên gia tốt nghiệp từ các trường đại học Đức hàng
                đầu, hiểu rõ từng bước của hành trình từ ngày đầu đến ngày nhập
                học.</span
              >
            </p>
            <a href="/ve-chung-toi" class="link-custom" data-i18n="idx-about-link"
              >Tìm hiểu về chúng tôi</a
            >
          </div>
          <div class="about-images animate-hidden delay-200">
            <div class="grid-images">
              <img
                src="https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=600&q=80"
                class="grid-img"
                alt="Trường Đức 1"
              />
              <img
                src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=600&auto=format&fit=crop"
                class="grid-img"
                alt="Trường Đức 2"
              />
              <div class="stat-box">
                <h3>300+</h3>
                <p data-i18n="idx-stat-partners">Đối tác trường Đại học</p>
              </div>
              <img
                src="https://images.unsplash.com/photo-1607237138185-eedd9c632b0b?q=80&w=600&auto=format&fit=crop"
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
            <span data-i18n="idx-process-pretitle">✨ Quy trình du học</span>
          </div>
          <h2 class="section-title" data-i18n="idx-process-title">
            05 bước đến giảng đường nước Đức
          </h2>
        </div>
        <div class="timeline-wrap">
          <div class="timeline-grid">
            <div class="timeline-step animate-hidden delay-100">
              <div class="circle-step">01</div>
              <h4 class="step-title" data-i18n="idx-step1-title">Tư vấn miễn phí</h4>
              <p class="step-desc" data-i18n="idx-step1-desc">
                Đánh giá hồ sơ và định hướng lộ trình phù hợp
              </p>
            </div>
            <div class="timeline-step animate-hidden delay-200">
              <div class="circle-step">02</div>
              <h4 class="step-title" data-i18n="idx-step2-title">Luyện tiếng Đức</h4>
              <p class="step-desc" data-i18n="idx-step2-desc">
                Đạt trình độ ngôn ngữ theo yêu cầu của trường
              </p>
            </div>
            <div class="timeline-step animate-hidden delay-300">
              <div class="circle-step">03</div>
              <h4 class="step-title" data-i18n="idx-step3-title">Chuẩn bị hồ sơ</h4>
              <p class="step-desc" data-i18n="idx-step3-desc">
                Hoàn thiện đầy đủ giấy tờ và nộp đơn
              </p>
            </div>
            <div class="timeline-step animate-hidden delay-400">
              <div class="circle-step">04</div>
              <h4 class="step-title" data-i18n="idx-step4-title">Xin visa</h4>
              <p class="step-desc" data-i18n="idx-step4-desc">
                Hỗ trợ toàn bộ thủ tục visa sinh viên
              </p>
            </div>
            <div class="timeline-step animate-hidden delay-500">
              <div class="circle-step">05</div>
              <h4 class="step-title" data-i18n="idx-step5-title">Nhập học &amp; ổn định</h4>
              <p class="step-desc" data-i18n="idx-step5-desc">
                Đồng hành từ ngày đầu đến Đức
              </p>
            </div>
          </div>
        </div>
        <div class="text-center animate-hidden">
          <a
            href="/tu-van/quy-trinh-tu-van"
            class="link-more"
            data-i18n="idx-process-link"
            >Xem lộ trình chi tiết</a
          >
        </div>
      </div>
    </section>

    <!-- 5. TESTIMONIALS -->
    <section class="testimonial-section">
      <div class="page-container">
        <h2 class="section-title animate-hidden" data-i18n="idx-testimonial-title">
          Những hành trình đáng tự hào
        </h2>
        <div class="testimonial-grid">
          <div class="testimonial-item animate-hidden delay-100">
            <div class="testimonial-card">
              <p class="testimonial-text" data-i18n="idx-testimonial-1">
                Nhờ có VIGER EDU, em đã tìm được ngôi trường đại học phù hợp nhất
                tại Đức cho ngành kỹ thuật của mình, sự hỗ trợ từ các anh chị thực
                sự tuyệt vời!
              </p>
              <div class="author-box">
                <img
                  src="{{ asset('img/duc_nguyen.jpg') }}"
                  alt="Duc Nguyen"
                  style="width: 36px; height: 36px; border-radius: var(--radius-full)"
                />
                <span>Duc Nguyen</span>
              </div>
            </div>
          </div>
          <div class="testimonial-item animate-hidden delay-100">
            <div class="testimonial-card">
              <p class="testimonial-text" data-i18n="idx-testimonial-2">
                Em chưa từng nghĩ việc du học lại có thể dễ dàng đến thế! VIGER
                EDU đã giúp mọi thủ tục trở nên vô cùng nhanh chóng và nhẹ nhàng.
              </p>
              <div class="author-box">
                <img
                  src="{{ asset('img/mai_tran.jpg') }}"
                  alt="Mai Tran"
                  style="width: 36px; height: 36px; border-radius: var(--radius-full)"
                />
                <span>Mai Tran</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 6. CTA SECTION -->
    <section class="cta-section animate-hidden">
      <div class="page-container">
        <div class="section-pretitle" style="justify-content: center">
          <span data-i18n="idx-cta-pretitle">✨ Bắt đầu hành trình của riêng bạn</span>
        </div>
        <h2 class="cta-title" data-i18n="idx-cta-title">
          Khởi đầu hành trình chinh phục tương lai
        </h2>
        <p class="cta-desc" data-i18n="idx-cta-desc">
          Liên hệ với chúng tôi ngay hôm nay để được tư vấn lộ trình học tập và
          sự nghiệp vững chắc tại nước ngoài.
        </p>
        <a class="cta-pill" href="/lien-he" data-i18n="idx-cta-btn">Bắt đầu ngay</a>
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
      <img src="{{ asset('img/lttv.jpg') }}" alt="Lộ trình tư vấn" />
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