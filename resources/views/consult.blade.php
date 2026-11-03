@extends('layouts.app')

@section('title', 'Tư vấn Du học')

@php
  // 4 bước quy trình. 'icon' là phần path bên trong thẻ <svg>.
  $steps = [
    [
      'title' => 'Định hướng & Đánh giá',
      'body' => 'Đánh giá năng lực học tập, tiếng Đức và tài chính. Tư vấn chọn ngành, chọn trường (Uni/FH/Studienkolleg) phù hợp nhất với nguyện vọng.',
      'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    ],
    [
      'title' => 'Hoàn thiện Hồ sơ',
      'body' => 'Hỗ trợ dịch thuật, công chứng, thẩm tra APS, thi TestAS. Chuẩn bị Motivation Letter (Thư động lực) và CV chuẩn form Đức.',
      'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    ],
    [
      'title' => 'Xin Zu & Tài chính',
      'body' => 'Gửi hồ sơ xin giấy gọi nhập học (Zulassung) tới các trường qua Uni-Assist. Hỗ trợ mở tài khoản phong tỏa (Sperrkonto) nhanh chóng.',
      'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    ],
    [
      'title' => 'Visa & Xuất cảnh',
      'body' => 'Luyện phỏng vấn Visa trực tiếp với chuyên gia. Hỗ trợ đặt vé máy bay, tìm nhà ở và đưa đón tại sân bay Đức.',
      'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ],
  ];

  // Điều kiện du học hệ Đại học
  $requirements = [
    [
      'title' => 'Học vấn & Kỳ thi Tốt nghiệp THPT',
      'body' => 'Thi đỗ kỳ thi Tốt nghiệp THPT Quốc gia với tổng điểm 6 môn (Toán, Văn, Ngoại ngữ và 3 môn tổ hợp) đạt từ 36 điểm trở lên. Không môn nào dưới 4, và có ít nhất 4 môn đạt từ 6 trở lên.',
    ],
    [
      'title' => 'Trúng tuyển Đại học tại Việt Nam',
      'body' => 'Đã trúng tuyển vào hệ Đào tạo Đại học Chính quy tại một Trường Đại học Việt Nam được công nhận (theo xếp hạng H+ trên Anabin).',
    ],
    [
      'title' => 'Ngoại ngữ (Tiếng Đức / Tiếng Anh)',
      'body' => 'Chứng chỉ tiếng Đức B1/B2 (Goethe, ÖSD, Telc) hoặc IELTS 6.0+ (nếu học chương trình tiếng Anh). Chứng chỉ TestAS và chứng nhận thẩm tra APS.',
    ],
  ];
@endphp

{{-- CSS riêng của trang (cần @stack('styles') trong layout) --}}
@push('styles')
  <style>
    /* Layout Elements */
    .consult-section {
      padding: 5rem 1.5rem;
    }
    .consult-section-bg {
      background-color: var(--bg-alt);
    }
    .consult-container {
      max-width: 80rem;
      margin: 0 auto;
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

    /* Steps Grid */
    .steps-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
    }
    @media (min-width: 768px) {
      .steps-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    @media (min-width: 1024px) {
      .steps-grid {
        grid-template-columns: repeat(4, 1fr);
      }
    }

    .step-card {
      background: var(--bg-white);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-xl);
      padding: 2.5rem 2rem;
      text-align: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    .step-card:hover {
      transform: translateY(-10px);
      box-shadow: var(--shadow-xl);
    }
    .step-number {
      position: absolute;
      top: -15px;
      right: -15px;
      font-size: 6rem;
      font-weight: 900;
      color: rgba(193, 18, 31, 0.05); /* Viger Red with low opacity */
      line-height: 1;
      transition: color 0.3s ease;
    }
    .step-card:hover .step-number {
      color: rgba(193, 18, 31, 0.1);
    }
    .step-icon {
      width: 64px;
      height: 64px;
      background: rgba(193, 18, 31, 0.1);
      color: var(--accent-main);
      border-radius: var(--radius-full);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      font-size: 1.5rem;
    }
    .step-icon svg {
      width: 32px;
      height: 32px;
    }
    .step-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 1rem;
    }
    .step-desc {
      color: var(--text-secondary);
      font-size: 0.95rem;
      line-height: 1.6;
    }

    /* Requirements Section */
    .req-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 3rem;
      align-items: center;
    }
    @media (min-width: 1024px) {
      .req-grid {
        grid-template-columns: 1fr 1fr;
      }
    }
    .req-img {
      border-radius: var(--radius-2xl);
      overflow: hidden;
      box-shadow: var(--shadow-lg);
    }
    .req-img img {
      width: 100%;
      height: auto;
      display: block;
    }
    .req-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }
    .req-item {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
    }
    .req-icon {
      background: var(--text-primary);
      color: white;
      width: 28px;
      height: 28px;
      border-radius: var(--radius-full);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      margin-top: 4px;
    }
    .req-icon svg {
      width: 16px;
      height: 16px;
    }
    .req-text h4 {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 0.25rem;
    }
    .req-text p {
      font-size: 0.95rem;
      color: var(--text-secondary);
      line-height: 1.5;
    }

    /* Form Section */
    .form-wrapper {
      background: var(--bg-white);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-2xl);
      padding: 3rem;
      box-shadow: var(--shadow-lg);
      max-width: 800px;
      margin: 0 auto;
    }
    .form-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }
    @media (min-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr 1fr;
      }
    }
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    .form-group.full-width {
      grid-column: 1 / -1;
    }
    .form-label {
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--text-primary);
    }
    .form-input {
      padding: 0.8rem 1rem;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      font-family: inherit;
      font-size: 1rem;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .form-input:focus {
      outline: none;
      border-color: var(--accent-main);
      box-shadow: 0 0 0 3px rgba(193, 18, 31, 0.1);
    }
    textarea.form-input {
      min-height: 120px;
      resize: vertical;
    }
    .submit-btn {
      background: var(--accent-main);
      color: white;
      border: none;
      padding: 1rem 2rem;
      font-size: 1.1rem;
      font-weight: 700;
      border-radius: var(--radius-md);
      cursor: pointer;
      transition: background 0.3s ease, transform 0.2s ease;
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 0.5rem;
    }
    .submit-btn:hover {
      background: var(--accent-main-hover);
      transform: translateY(-2px);
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
              >Chương trình Đại học &amp; Cao học</span
            >
          </div>
          <h1 class="hero-heading" style="color: white; margin-bottom: 1rem">
            TƯ VẤN DU HỌC
          </h1>
          <p class="cta-desc" style="max-width: 750px; margin: 1.5rem auto 2.5rem; font-weight: 300;">
            Khởi đầu hành trình học thuật tại Đức với sự đồng hành sát sao từ
            chuyên gia. Chúng tôi giúp bạn định hướng ngành nghề, chuẩn bị hồ sơ
            và xin Visa với tỷ lệ đỗ cao nhất.
          </p>
        </div>
      </div>
    </section>

    <!-- QUY TRÌNH 4 BƯỚC -->
    <section class="consult-section">
      <div class="consult-container">
        <div class="section-heading-center animate-hidden">
          <h2>Lộ trình đồng hành cùng VIGER EDU</h2>
          <p>
            Quy trình làm việc chuyên nghiệp, minh bạch giúp bạn hoàn thành giấc
            mơ du học Đức một cách dễ dàng và nhanh chóng nhất.
          </p>
        </div>

        <div class="steps-grid">
          @foreach ($steps as $step)
            <div class="step-card animate-hidden delay-{{ $loop->iteration * 100 }}">
              <div class="step-number">{{ $loop->iteration }}</div>
              <div class="step-icon">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="{{ $step['icon'] }}"
                  />
                </svg>
              </div>
              <h3 class="step-title">{{ $step['title'] }}</h3>
              <p class="step-desc">{{ $step['body'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <!-- ĐIỀU KIỆN CƠ BẢN -->
    <section class="consult-section consult-section-bg">
      <div class="consult-container req-grid">
        <div class="req-img animate-hidden delay-100">
          <img
            src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1000&q=80"
            alt="Sinh viên"
          />
        </div>
        <div class="req-content animate-hidden delay-300">
          <h2 style="font-size: 2rem; color: var(--text-primary); font-weight: 800; margin-bottom: 2rem;">
            Điều kiện du học hệ Đại học
          </h2>
          <ul class="req-list">
            @foreach ($requirements as $req)
              <li class="req-item">
                <div class="req-icon">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="3"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <div class="req-text">
                  <h4>{{ $req['title'] }}</h4>
                  <p>{{ $req['body'] }}</p>
                </div>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/animations.js') }}"></script>
@endpush