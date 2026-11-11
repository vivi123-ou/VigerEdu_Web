@extends('layouts.admin', ['menuKey' => 'dashboard'])

@section('title', 'Bảng điều khiển')

@section('content')
    <p class="lead">Xin chào {{ auth()->user()->name }}. Bạn muốn làm gì hôm nay? Bấm vào một ô bên dưới. Trang nào cũng có nút <strong>?</strong> màu vàng cạnh tiêu đề để xem hướng dẫn.</p>

    @php
        // Việc hay làm (≤ 3 lần bấm), chỉ hiện việc thuộc vai trò của người đăng nhập.
        $shortcuts = collect([
            ['question-bank', 'list', 'Thư viện câu hỏi', 'Duyệt, sửa câu và trọng số rút đề.'],
            ['test-settings', 'sliders', 'Cài đặt bài test', 'Số câu, ngưỡng, nhãn mức độ quan tâm.'],
            ['majors', 'chart', 'Ngành học & nghề', 'Danh mục ngành tại Đức, nghề nghiệp.'],
            ['form-fields', 'form', 'Trường thông tin khách', 'Ô bắt buộc, ô gửi sang CRM.'],
            ['intro', 'image', 'Sửa trang giới thiệu', 'Bấm vào chữ, ảnh trên trang để sửa ngay.'],
            ['media', 'image', 'Thư viện ảnh', 'Tải ảnh banner, ảnh khối nội dung.'],
            ['appearance', 'palette', 'Hotline, logo, chân trang', 'Thông tin liên hệ, màu, menu đầu trang.'],
        ])->filter(fn ($item) => auth()->user()->canAccessMenu($item[0]))->take(6);
    @endphp
    <div class="guide">
        @foreach ($shortcuts as [$key, $icon, $title, $text])
            <a class="guide__item" href="{{ \App\Support\AdminMenu::url(\App\Support\AdminMenu::find($key)) }}">
                <span class="guide__icon">@include('partials.icon', ['name' => $icon, 'size' => 22])</span>
                <span>
                    <p class="guide__title">{{ $title }}</p>
                    <p class="guide__text">{{ $text }}</p>
                </span>
            </a>
        @endforeach
    </div>
@endsection
