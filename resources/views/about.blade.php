@extends('layouts.app')

@section('title', 'Về chúng tôi')

@use('App\Support\AboutPage')

@php
  $about = AboutPage::all();
  $heroImg = AboutPage::imageUrl($about['about_hero_image'], AboutPage::DEFAULT_HERO_IMAGE);
  $introImg = AboutPage::imageUrl($about['about_intro_image'], AboutPage::DEFAULT_INTRO_IMAGE);
  $historyImg = AboutPage::imageUrl($about['about_history_image'], AboutPage::DEFAULT_HISTORY_IMAGE);
  $accordionItems = $about['about_values_items'] ?? [];
@endphp

@section('content')
  <main class="main-content">
    <!-- PAGE HEADER SECTION -->
    <section class="cta-section animate-hidden" style="margin: 0; border-radius: 0; padding: 10rem 0 6rem; background-image: linear-gradient(rgba(18, 38, 63, 0.8), rgba(18, 38, 63, 0.9)), url('{{ $heroImg }}'); background-size: cover; background-position: center; border-bottom: 4px solid var(--accent-main);">
      <div class="page-container">
        <div class="text-center animate-hidden">
          <div class="section-pretitle" style="justify-content: center; margin-bottom: 1rem;">
            <span style="color: var(--accent-alt); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">{{ $about['about_hero_pretitle'] }}</span>
          </div>
          <h1 class="hero-heading" style="color: white;">{{ $about['about_hero_title'] }}</h1>
          <p class="cta-desc" style="max-width: 700px; margin: 1.5rem auto 0; font-weight: 300;">
            {{ $about['about_hero_description'] }}
          </p>
        </div>
      </div>
    </section>

    <!-- GIỚI THIỆU LỘ TRÌNH -->
    <section class="about-section">
      <div class="page-container">
        <div class="hero-grid">
          <!-- Cột trái: Hình ảnh -->
          <div class="hero-image animate-hidden">
            <img
              src="{{ $introImg }}"
              alt="{{ $about['about_intro_heading'] }}"
            />
          </div>

          <!-- Cột phải: Text -->
          <div class="hero-text animate-hidden delay-100">
            <div class="section-pretitle">
              <span style="color: var(--accent-alt-hover); background-color: #fff9e6; padding: 0.35rem 1rem; border-radius: var(--radius-full); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">{{ $about['about_intro_label'] }}</span>
            </div>
            <h2 class="about-heading" style="margin-top: 1.5rem;">
              {{ $about['about_intro_heading'] }}
            </h2>

            <div style="background: var(--bg-white); padding: 2rem; border-radius: var(--radius-xl); box-shadow: var(--shadow-md); margin-bottom: 1.5rem; border-left: 4px solid var(--accent-main);">
              <p class="about-desc" style="margin-bottom: 0;">
                {!! AboutPage::highlight($about['about_intro_highlight'], 'var(--accent-main); font-weight: 700') !!}
              </p>
            </div>

            @foreach (AboutPage::paragraphs($about['about_intro_description']) as $paragraph)
              <p class="about-desc">{{ $paragraph }}</p>
            @endforeach
          </div>
        </div>
      </div>
    </section>

    <!-- NÂNG BƯỚC HỌC VIÊN SECTION (ACCORDION) -->
    <section style="padding: 8rem 0; background-color: var(--border-light);">
      <div class="page-container">
        <div class="hero-grid">
          <!-- Cột trái: Tiêu đề và giới thiệu -->
          <div class="hero-text animate-hidden">
            <div style="width: 50px; height: 4px; background: var(--accent-main); margin-bottom: 1.5rem; border-radius: 2px;"></div>
            <h2 class="about-heading" style="font-size: clamp(2rem, 3vw, 2.8rem);">
              {!! nl2br(e($about['about_values_heading']), false) !!}
            </h2>
            <p class="about-desc">{{ $about['about_values_description'] }}</p>
          </div>

          <!-- Cột phải: Accordion -->
          <div class="animate-hidden delay-100" id="customAccordion">
            @foreach ($accordionItems as $item)
              <div class="acc-item" style="background: var(--bg-white); border-radius: var(--radius-xl); margin-bottom: 1.25rem; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); overflow: hidden;">
                <button class="acc-header" style="width: 100%; text-align: left; background: none; border: none; padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; cursor: pointer; font-size: 1.05rem; font-weight: 600; color: var(--text-primary); font-family: inherit;">
                  <span style="padding-right: 1rem;">{{ $item['title'] ?? '' }}</span>
                  <span class="acc-icon" style="font-size: 1.8rem; font-weight: 300; transition: transform 0.3s ease; color: var(--text-muted);">+</span>
                </button>
                <div class="acc-content" style="max-height: 0; padding: 0 2rem; opacity: 0; overflow: hidden; transition: all 0.4s ease;">
                  <p class="about-desc" style="margin: 0; padding-bottom: 1.5rem;">
                    {{ $item['body'] ?? '' }}
                  </p>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </section>

    <!-- HÀNH TRÌNH TƯ VẤN SECTION -->
    <section class="about-section">
      <div class="page-container">
        <div class="hero-grid">
          <!-- Cột trái: Text -->
          <div class="hero-text animate-hidden">
            <div class="section-pretitle">
              <span style="color: var(--accent-alt-hover); background-color: #fff9e6; padding: 0.35rem 1rem; border-radius: var(--radius-full); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">{{ $about['about_history_label'] }}</span>
            </div>
            <h2 class="about-heading" style="margin-top: 1.5rem;">
              {{ $about['about_history_heading'] }}
            </h2>

            @foreach (AboutPage::paragraphs($about['about_history_description']) as $paragraph)
              <p class="about-desc">{{ $paragraph }}</p>
            @endforeach
          </div>

          <!-- Cột phải: Hình ảnh -->
          <div class="hero-image animate-hidden delay-100">
            <img
              src="{{ $historyImg }}"
              alt="{{ $about['about_history_heading'] }}"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- CTA SECTION -->
    <section class="cta-section animate-hidden">
      <div class="page-container">
        <div class="section-pretitle" style="justify-content: center;">
          <span>{{ $about['about_cta_pretitle'] }}</span>
        </div>
        <h2 class="cta-title">{{ $about['about_cta_title'] }}</h2>
        <p class="cta-desc">{{ $about['about_cta_description'] }}</p>
        <a class="cta-pill" href="{{ $about['about_cta_button_url'] }}">{{ $about['about_cta_button_text'] }}</a>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/animations.js') }}"></script>

  {{-- Script điều khiển Accordion --}}
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const headers = document.querySelectorAll(".acc-header");

      headers.forEach((header) => {
        header.addEventListener("click", function () {
          const currentItem = this.parentElement;
          const isActive = currentItem.classList.contains("active");

          // Đóng tất cả các mục khác
          document.querySelectorAll(".acc-item").forEach((item) => {
            item.classList.remove("active");
            const btn = item.querySelector(".acc-header");
            btn.style.color = "var(--text-primary)";
            const icon = item.querySelector(".acc-icon");
            icon.textContent = "+";
            icon.style.transform = "rotate(0deg)";
            icon.style.color = "var(--text-muted)";

            const content = item.querySelector(".acc-content");
            content.style.maxHeight = "0";
            content.style.opacity = "0";
          });

          // Mở mục được click
          if (!isActive) {
            currentItem.classList.add("active");
            this.style.color = "var(--accent-main)";

            const icon = this.querySelector(".acc-icon");
            icon.textContent = "−";
            icon.style.transform = "rotate(180deg)";
            icon.style.color = "var(--accent-main)";

            const content = currentItem.querySelector(".acc-content");
            content.style.maxHeight = content.scrollHeight + 40 + "px";
            content.style.opacity = "1";
          }
        });
      });
    });
  </script>
@endpush
