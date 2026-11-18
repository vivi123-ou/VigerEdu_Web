{{--
    Nhập mã xác nhận (H4) theo mẫu luong-khach.html (màn hình 3b): email che bớt, ô nhập 6 số, nút "Xem báo cáo",
    "Gửi lại mã" (đếm ngược theo cài đặt otp_resend_seconds), "Sửa email" (về form, giữ chữ đã điền).
    Máy chạy thử (APP_ENV=local, APP_DEBUG=true) hiện luôn mã để thử nhanh; máy chủ thật không bao giờ hiện.
--}}
@extends('layouts.public')

@section('title', setting('otp_title'))
@section('noindex', true)

@section('content')
    <div class="ksec">
        <div class="otpbox">
            <h1 class="uf__title">{{ setting('otp_title', 'Nhập mã xác nhận') }}</h1>
            <p class="muted">{{ str_replace('{email}', $maskedEmail, (string) setting('otp_sent_text', 'Mã 6 số đã gửi tới {email}.')) }}</p>

            @if ($devCode)
                <p class="devcode">Máy chạy thử: mã là <b>{{ $devCode }}</b></p>
            @endif
            @if (session('status'))
                <p class="okmsg" role="status">{{ session('status') }}</p>
            @endif

            <form method="post" action="{{ route('report.verify') }}">
                @csrf
                <label class="visually-hidden" for="otp">{{ setting('otp_title', 'Nhập mã xác nhận') }}</label>
                <input id="otp" @class(['otp-in', 'is-err' => $errors->has('code')]) name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                       maxlength="{{ $length }}" pattern="[0-9]*" autofocus required>
                @error('code')
                    <p class="err-msg" role="alert">{{ $message }}</p>
                @enderror
                <button class="kbtn kbtn--red" type="submit" style="width:100%;margin-top:14px">{{ setting('otp_button', 'Xem báo cáo') }}</button>
            </form>

            <div class="otp-tools">
                <form method="post" action="{{ route('report.resend') }}">
                    @csrf
                    <button class="linkbtn" type="submit" data-resend data-wait="{{ $wait }}" @disabled($wait > 0)>
                        {{ setting('otp_resend_text', 'Gửi lại mã') }}<span data-countdown></span>
                    </button>
                </form>
                <span aria-hidden="true">·</span>
                <a href="{{ route('report.request') }}">{{ setting('otp_edit_email', 'Sửa email') }}</a>
            </div>
            @if (filled(setting('otp_spam_hint')))
                <p class="tiny">{{ setting('otp_spam_hint') }}</p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/khach.js') }}" defer></script>
@endpush
