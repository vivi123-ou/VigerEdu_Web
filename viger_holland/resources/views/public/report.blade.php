{{--
    Trang báo cáo đầy đủ (H4). Vân chốt 18/11: báo cáo xem được trong THẺ (tab) đã nhập đúng mã, tải lại vẫn còn;
    đóng thẻ thì mất (muốn xem lại thì làm bài lại).
    Cách làm: nhập đúng mã → thẻ nhận một "chìa khóa xem" lưu trong sessionStorage của chính thẻ đó (trình duyệt tự xóa khi
    đóng thẻ, không có trên đường link). Tải lại trang thì khach.js gửi chìa khóa lên ReportController::view để lấy lại báo cáo.
    Thẻ mới / không có chìa khóa → "Báo cáo đã đóng".
--}}
@extends('layouts.public')

@section('title', setting('report_kicker'))
@section('noindex', true)

@section('content')
    <div class="ksec" data-report-page data-view-url="{{ route('report.view') }}" @if ($key) data-key="{{ $key }}" @endif>
        <div data-report-holder>
            @if ($report)
                @include('public.partials.report-body', ['report' => $report])
            @else
                <p class="tiny" style="text-align:center" data-report-loading>{{ setting('report_loading', 'Đang mở báo cáo…') }}</p>
            @endif
        </div>

        <div class="otpbox" data-report-closed hidden>
            @include('public.partials.report-closed-box')
        </div>
        @unless ($report)
            <noscript><div class="otpbox">@include('public.partials.report-closed-box')</div></noscript>
        @endunless
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/khach.js') }}" defer></script>
@endpush
