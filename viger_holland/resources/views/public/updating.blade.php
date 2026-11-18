{{-- Trang tạm cho phần chưa làm xong (ví dụ "Xem báo cáo đầy đủ" trước khi có H4). --}}
@extends('layouts.public')

@section('title', setting('test_name'))
@section('noindex', true)

@section('content')
    <div class="ksec">
        <div class="narrow" style="padding:40px 20px;text-align:center">
            <h1 class="serif" style="font-size:30px;margin:0 0 10px">Phần này đang được hoàn thiện</h1>
            <p style="color:var(--ink2);margin:0 0 24px">Bạn vui lòng quay lại sau ít ngày. Cảm ơn bạn đã làm bài trắc nghiệm.</p>
            <a class="kbtn" href="{{ route('home') }}">Về trang đầu</a>
        </div>
    </div>
@endsection
