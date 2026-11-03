@extends('layouts.app')

@section('title', 'Quy trình Tư vấn')

@php
  // 5 bước quy trình
  $steps = [
    ['Tư vấn miễn phí',     'Đánh giá hồ sơ và định hướng lộ trình phù hợp'],
    ['Luyện tiếng Đức',     'Đạt trình độ ngôn ngữ theo yêu cầu của trường'],
    ['Chuẩn bị hồ sơ',      'Hoàn thiện đầy đủ giấy tờ và nộp đơn'],
    ['Xin visa',            'Hỗ trợ toàn bộ thủ tục visa sinh viên'],
    ['Nhập học & ổn định',  'Đồng hành từ ngày đầu đến Đức'],
  ];

  // 4 dịch vụ tư vấn. 'icon' là thuộc tính d của <path>.
  $services = [
    [
      'title' => 'Định hướng Ngành/Trường',
      'body'  => 'Phân tích học bạ, đánh giá năng lực tài chính và định hướng lộ trình vào các trường Đại học hàng đầu.',
      'icon'  => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    ],
    [
      'title' => 'Hoàn thiện Hồ sơ',
      'body'  => 'Dịch thuật, công chứng, viết Motivation Letter, xin chứng nhận APS và hỗ trợ nộp qua Uni-Assist.',
      'icon'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    ],
    [
      'title' => 'Thủ tục Visa & Tài chính',
      'body'  => 'Hỗ trợ mở tài khoản phong tỏa (Sperrkonto), luyện phỏng vấn Visa trực tiếp với tỷ lệ đỗ cao.',
      'icon'  => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ],
    [
      'title' => 'Hỗ trợ sau khi bay',
      'body'  => 'Tìm kiếm nhà ở, đón tại sân bay, hướng dẫn đăng ký hộ khẩu (Anmeldung) và nhập học tại trường.',
      'icon'  => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    ],
  ];
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
          url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1600&q=80');
        background-size: cover;
        background-position: center;
        border-bottom: 4px solid var(--accent-alt);
      "
    >
      <div class="page-container">
        <div class="text-center animate-hidden">
          <div class="section-pretitle" style="justify-content: center; margin-bottom: 1rem">
            <span style="color: var(--accent-alt); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;"
              >Đồng hành cùng bạn</span
            >
          </div>
          <h1 class="hero-heading" style="color: white; margin-bottom: 1rem">
            QUY TRÌNH TƯ VẤN DU HỌC
          </h1>
          <p class="cta-desc" style="max-width: 750px; margin: 1.5rem auto 2.5rem; font-weight: 300;">
            Minh bạch, chuyên nghiệp và tận tâm. Tại VIGER EDU, chúng tôi thiết kế
            một lộ trình rõ ràng giúp bạn dễ dàng theo dõi từng bước trên hành
            trình du học Đức của mình.
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
            <span>✨ Quy trình du học</span>
          </div>
          <h2 class="section-title">05 bước đến giảng đường nước Đức</h2>
        </div>

        <!-- Trục thời gian 5 bước -->
        <div class="timeline-wrap">
          <div class="timeline-grid">
            @foreach ($steps as [$title, $desc])
              <div class="timeline-step animate-hidden delay-{{ $loop->iteration * 100 }}">
                <div class="circle-step">{{ sprintf('%02d', $loop->iteration) }}</div>
                <h4 class="step-title">{{ $title }}</h4>
                <p class="step-desc">{{ $desc }}</p>
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
            <span>✨ Dịch vụ tư vấn</span>
          </div>
          <h2>Toàn diện cho du học sinh</h2>
          <p>
            Cung cấp hệ sinh thái dịch vụ trọn gói từ những bước chuẩn bị đầu tiên
            tại Việt Nam cho tới khi bạn hoàn toàn hòa nhập tại Đức.
          </p>
        </div>

        <div class="services-grid">
          @foreach ($services as $service)
            <div class="service-card animate-hidden delay-{{ $loop->iteration * 100 }}">
              <div class="service-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="{{ $service['icon'] }}" />
                </svg>
              </div>
              <h3 class="service-title">{{ $service['title'] }}</h3>
              <p class="service-desc">{{ $service['body'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <!-- ĐĂNG KÝ TƯ VẤN (CTA SECTION) -->
    <section class="cta-section animate-hidden">
      <div class="page-container">
        <div class="section-pretitle" style="justify-content: center;">
          <span>✨ Bắt đầu hành trình của riêng bạn</span>
        </div>
        <h2 class="cta-title">Khởi đầu hành trình chinh phục tương lai</h2>
        <p class="cta-desc">
          Liên hệ với chúng tôi ngay hôm nay để được tư vấn lộ trình học tập và
          sự nghiệp vững chắc tại nước ngoài.
        </p>
        <a class="cta-pill" href="/lien-he">Bắt đầu ngay</a>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/animations.js') }}"></script>
@endpush