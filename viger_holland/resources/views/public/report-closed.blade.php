{{--
    Báo cáo đã đóng (H4): báo cáo đầy đủ chỉ hiện một lần ngay sau khi nhập đúng mã (Vân chốt 18/11).
    Tải lại, mở lại hay quay lại trang đều tới đây; muốn xem lại thì làm bài lại với câu hỏi mới.
--}}
@extends('layouts.public')

@section('title', setting('report_closed_title'))
@section('noindex', true)

@section('content')
    <div class="ksec">
        <div class="otpbox">
            <h1 class="uf__title">{{ setting('report_closed_title', 'Báo cáo đã đóng') }}</h1>
            <p class="muted">{{ setting('report_closed_text') }}</p>
            <form method="post" action="{{ route('test.start') }}" style="margin-top:22px">
                @csrf
                <button class="kbtn kbtn--red" type="submit">{{ setting('report_closed_button', 'Làm bài lại') }} <svg class="ic"><use href="#i-arrow-right"/></svg></button>
            </form>
        </div>
    </div>
@endsection
