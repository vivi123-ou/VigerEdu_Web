@extends('layouts.category')

@section('title', 'Chương trình Đào tạo')
@section('banner_image', 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1600&q=80')
@section('banner_color', 'rgba(18, 38, 63, 0.8)')
@section('banner_pretitle', 'Đa Dạng Lựa Chọn')
@section('banner_heading', 'CHƯƠNG TRÌNH ĐÀO TẠO')
@section('banner_desc', 'Dù bạn vừa tốt nghiệp THPT, muốn nâng cao bằng cấp hay học nghề có lương, hệ thống giáo dục Đức luôn có một lộ trình hoàn hảo dành riêng cho bạn.')

@section('after_content')
  <section class="cta-section animate-hidden">
    <div class="page-container">
      <div class="section-pretitle" style="justify-content: center">
        <span>✨ Bắt đầu hành trình của riêng bạn</span>
      </div>
      <h2 class="cta-title">Chưa biết chọn chương trình nào?</h2>
      <p class="cta-desc">
        Để lại thông tin, chuyên gia của VIGER EDU sẽ tư vấn lộ trình học tập
        miễn phí và phù hợp nhất với năng lực của bạn.
      </p>
      <a class="cta-pill" href="/lien-he">Nhận tư vấn ngay</a>
    </div>
  </section>
@endsection