@extends('layouts.admin', ['menuKey' => 'dashboard'])

@section('title', 'Bảng điều khiển')

@section('content')
    <p class="lead">Xin chào {{ auth()->user()->name }}! Đây là tình hình bài test hiện tại.</p>

    <div class="stats">
        <div class="stat">
            <p class="stat__value">{{ $partACount }}</p>
            <p class="stat__label">Câu hỏi thông tin khách (Phần A) đang bật</p>
        </div>
        <div class="stat">
            <p class="stat__value">{{ $partBCount }}</p>
            <p class="stat__label">Câu hỏi Holland (Phần B) đang bật</p>
        </div>
        <div class="stat">
            <p class="stat__value">{{ $sets->count() }}</p>
            <p class="stat__label">Bộ câu hỏi</p>
        </div>
        <div class="stat stat--muted">
            <p class="stat__value">–</p>
            <p class="stat__label">Lượt làm bài (có từ giai đoạn 3)</p>
        </div>
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
