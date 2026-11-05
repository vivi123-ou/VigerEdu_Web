@extends('layouts.app')

@section('title', 'Quy trình Tư vấn')

@use('App\Support\ConsultProcessPage')

@php
  $content = ConsultProcessPage::all();
  $heroImg = ConsultProcessPage::imageUrl($content['consult_process_hero_image'], ConsultProcessPage::DEFAULT_HERO_IMAGE);
  $steps = $content['consult_process_steps'] ?? [];
  $services = $content['consult_process_services'] ?? [];
@endphp

@push('styles')
  <style>
    /* Layout Elements */
    .consult-section {
      padding: 6rem 1.5rem;
    }
    .consult-section-bg {
      background-color: var(--bg-alt);
    }

    /* Headers */
    .section-heading-center {
      text-align: center;
      margin-bottom: 3.5rem;
    }
    .section-heading-center h2 {
      font-size: 2.25rem;
      color: var(--text-primary);
      font-weight: 800;
      margin-bottom: 1rem;
    }
    .section-heading-center p {
      color: var(--text-secondary);
      font-size: 1.1rem;
      max-width: 600px;
      margin: 0 auto;
    }

    /* Services Grid (Dịch vụ tư vấn) */
    .services-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
    }
    @media (min-width: 768px) {
      .services-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    @media (min-width: 1024px) {
      .services-grid {
        grid-template-columns: repeat(4, 1fr);
      }
    }

    .service-card {
      background: var(--bg-white);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-xl);
      padding: 2.5rem 2rem;
      text-align: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .service-card:hover {
      transform: translateY(-10px);
      box-shadow: var(--shadow-xl);
    }

    .service-icon {
      width: 64px;
      height: 64px;
      background: rgba(193, 18, 31, 0.1);
      color: var(--accent-main);
      border-radius: var(--radius-full);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
    }
    .service-icon svg {
      width: 32px;
      height: 32px;
    }
    .service-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 1rem;
    }
    .service-desc {
      color: var(--text-secondary);
      font-size: 0.95rem;
      line-height: 1.6;
    }
  </style>
@endpush

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
          linear-gradient(135deg, rgba(20, 20, 20, 0.9) 0%, rgba(66, 180, 146, 0.8) 100%),
          url('{{ $heroImg }}');
        background-size: cover;
        background-position: center;
        border-bottom: 4px solid var(--accent-alt);
      "
    >
      <div class="page-container">
        <div class="text-center animate-hidden">
          <div class="section-pretitle" style="justify-content: center; margin-bottom: 1rem">
            <span style="color: var(--accent-alt); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">{{ $content['consult_process_hero_pretitle'] }}</span>
          </div>
          <h1 class="hero-heading" style="color: white; margin-bottom: 1rem">
            {{ $content['consult_process_hero_title'] }}
          </h1>
          <p class="cta-desc" style="max-width: 750px; margin: 1.5rem auto 2.5rem; font-weight: 300;">
            {{ $content['consult_process_hero_description'] }}
          </p>
        </div>
      </div>
    </section>

    <!-- QUY TRÌNH DU HỌC -->
    <section class="process-section">
      <div class="page-container">
        <!-- Tiêu đề -->
        <div class="text-center animate-hidden">
          <div class="section-pretitle" style="margin: 0 auto 1rem;">
            <span>{{ $content['consult_process_steps_pretitle'] }}</span>
          </div>
          <h2 class="section-title">{{ $content['consult_process_steps_title'] }}</h2>
        </div>

        <!-- Trục thời gian 5 bước -->
        <div class="timeline-wrap">
          <div class="timeline-grid">
            @foreach ($steps as $step)
              <div class="timeline-step animate-hidden delay-{{ $loop->iteration * 100 }}">
                <div class="circle-step">{{ sprintf('%02d', $loop->iteration) }}</div>
                <h4 class="step-title">{{ $step['title'] ?? '' }}</h4>
                <p class="step-desc">{{ $step['description'] ?? '' }}</p>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </section>

    <!-- DỊCH VỤ TƯ VẤN - TOÀN DIỆN CHO DU HỌC SINH -->
    <section class="consult-section consult-section-bg">
      <div class="page-container">
        <div class="section-heading-center animate-hidden">
          <div class="section-pretitle" style="justify-content: center; margin-bottom: 1rem;">
            <span>{{ $content['consult_process_services_pretitle'] }}</span>
          </div>
          <h2>{{ $content['consult_process_services_title'] }}</h2>
          <p>{{ $content['consult_process_services_description'] }}</p>
        </div>

        <div class="services-grid">
          @foreach ($services as $service)
            <div class="service-card animate-hidden delay-{{ $loop->iteration * 100 }}">
              <div class="service-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="{{ ConsultProcessPage::iconPath($service['icon'] ?? null) }}" />
                </svg>
              </div>
              <h3 class="service-title">{{ $service['title'] ?? '' }}</h3>
              <p class="service-desc">{{ $service['body'] ?? '' }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <!-- ĐĂNG KÝ TƯ VẤN (CTA SECTION) -->
    <section class="cta-section animate-hidden">
      <div class="page-container">
        <div class="section-pretitle" style="justify-content: center;">
          <span>{{ $content['consult_process_cta_pretitle'] }}</span>
        </div>
        <h2 class="cta-title">{{ $content['consult_process_cta_title'] }}</h2>
        <p class="cta-desc">{{ $content['consult_process_cta_description'] }}</p>
        <a class="cta-pill" href="{{ $content['consult_process_cta_button_url'] }}">{{ $content['consult_process_cta_button_text'] }}</a>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/animations.js') }}"></script>
@endpush