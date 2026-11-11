{{-- Trang tạm khi luồng làm bài mới (Career Map) chưa xong. GĐ3 thay bằng: Thông tin → Làm bài → Báo cáo. --}}
@extends('layouts.public')

@section('title', setting('test_name'))

@section('content')
    <section class="container" style="padding:4rem 1rem;text-align:center">
        <h1>{{ setting('test_name') }}</h1>
        <p>Bài trắc nghiệm đang được cập nhật. Vui lòng quay lại sau.</p>
        <p><a class="btn" href="{{ route('home') }}">Về trang chủ</a></p>
    </section>
@endsection
