@extends('layouts.admin', ['menuKey' => 'dashboard'])

@section('title', 'Bảng điều khiển')

@section('content')
    <p class="lead">Xin chào {{ auth()->user()->name }}! Đây là tình hình bài test hiện tại. Chọn một mục ở menu bên trái để bắt đầu; mỗi trang đều có nút <strong>?</strong> cạnh tiêu đề để xem hướng dẫn.</p>

    <div class="guide">
        <a class="guide__item" href="{{ route('admin.section', 'question-sets') }}">
            <p class="guide__title">Sửa câu hỏi bài test</p>
            <p class="guide__text">Thêm, sửa, bật/tắt câu hỏi Holland.</p>
        </a>
        <a class="guide__item" href="{{ route('admin.section', 'intro') }}">
            <p class="guide__title">Đổi chữ, ảnh trang giới thiệu</p>
            <p class="guide__text">Tiêu đề, mô tả, banner, nút "Bắt đầu".</p>
        </a>
        <a class="guide__item" href="{{ route('home') }}" target="_blank" rel="noopener">
            <p class="guide__title">Xem trang như khách</p>
            <p class="guide__text">Mở trang bài test ở tab mới để kiểm tra.</p>
        </a>
    </div>

    <div class="stats">
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
        <a class="stat" href="{{ route('admin.attempts.index') }}" style="text-decoration:none">
            <p class="stat__value">{{ $attemptsToday }}</p>
            <p class="stat__label">Lượt làm bài hôm nay · tổng {{ $attemptsTotal }}</p>
        </a>
    </div>

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
@endsection
