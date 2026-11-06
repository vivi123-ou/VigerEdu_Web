@extends('layouts.admin', ['menuKey' => 'dashboard'])

@section('title', 'Bảng điều khiển')

@section('content')
    <p class="lead">Xin chào {{ auth()->user()->name }}. Bạn muốn làm gì hôm nay? Bấm vào một ô bên dưới. Trang nào cũng có nút <strong>?</strong> màu vàng cạnh tiêu đề để xem hướng dẫn.</p>

    @php
        // Việc hay làm (≤ 3 lần bấm), chỉ hiện việc thuộc vai trò của người đăng nhập.
        $shortcuts = collect([
            ['intro', 'image', 'Sửa trang giới thiệu', 'Bấm vào chữ, ảnh trên trang để sửa ngay.'],
            ['result-page', 'chart', 'Sửa trang kết quả', 'Lời chào, khối liên hệ tư vấn.'],
            ['question-sets', 'list', 'Sửa câu hỏi bài test', 'Thêm, sửa, ẩn câu hỏi Holland.'],
            ['info-form', 'form', 'Sửa form thông tin khách', 'Các câu khách điền để nhận kết quả.'],
            ['media', 'image', 'Thư viện ảnh', 'Tải ảnh banner, ảnh khối nội dung.'],
            ['email', 'mail', 'Sửa email gửi khách', 'Lời chào, lời kết, chữ ký.'],
            ['attempts', 'users', 'Xem lượt làm bài', 'Mã Holland và ngành gợi ý của từng lượt.'],
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

    @php $canEditQuestions = auth()->user()->canAccessMenu('question-sets'); @endphp
    <div class="stats">
        @if ($canEditQuestions)
        <div class="stat">
            <p class="stat__value">{{ $partACount }}</p>
            <p class="stat__label">Câu hỏi thông tin khách đang bật</p>
        </div>
        <div class="stat">
            <p class="stat__value">{{ $partBCount }}</p>
            <p class="stat__label">Câu hỏi Holland đang bật</p>
        </div>
        <div class="stat">
            <p class="stat__value">{{ $sets->count() }}</p>
            <p class="stat__label">Bộ câu hỏi</p>
        </div>
        @endif
        <a class="stat" href="{{ route('admin.attempts.index') }}" style="text-decoration:none">
            <p class="stat__value">{{ $attemptsToday }}</p>
            <p class="stat__label">Lượt làm bài hôm nay · tổng {{ $attemptsTotal }}</p>
        </a>
    </div>

    @if ($canEditQuestions)
    <section class="card">
        <h2 class="card__title">Bộ câu hỏi</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Tên bộ</th><th>Mã (dùng trong link)</th><th>Số câu đang bật</th><th>Trạng thái</th></tr>
                </thead>
                <tbody>
                    @forelse ($sets as $set)
                        <tr>
                            <td>{{ $set->name }} @if ($set->is_default)<span class="badge">Mặc định</span>@endif</td>
                            <td><code>?bo={{ $set->code }}</code></td>
                            <td>{{ $set->active_questions_count }}</td>
                            <td>{{ $set->is_active ? 'Đang bật' : 'Đang tắt' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Chưa có bộ câu hỏi nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @endif
@endsection
