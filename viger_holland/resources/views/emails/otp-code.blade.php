{{--
    Email mã xác nhận (H4). Chỉ có mã, không có link báo cáo. Chữ lấy từ cài đặt otp_email_*.
--}}
@php
    $brand = setting('brand_title', setting('site_name', 'VIGER EDU'));
    $red = setting('primary_color', '#C1121F');
@endphp
<!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $brand }}</title></head>
<body style="margin:0;padding:0;background:#f5f3ee;font-family:Arial,Helvetica,sans-serif;color:#12263f">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f3ee;padding:28px 12px">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border:1px solid #e3e7ec;border-radius:8px">
            <tr><td style="padding:22px 28px;border-bottom:1px solid #e3e7ec;font:700 18px Georgia,serif;color:#12263f">{{ $brand }}</td></tr>
            <tr><td style="padding:28px">
                <p style="margin:0 0 14px;font-size:16px">{{ setting('otp_email_intro', 'Mã xác nhận để mở báo cáo Holland của bạn là:') }}</p>
                <p style="margin:0 0 18px;font:700 34px/1.2 'Courier New',monospace;letter-spacing:8px;color:{{ $red }}">{{ $code }}</p>
                <p style="margin:0 0 8px;font-size:14px;color:#46576b">{{ str_replace('{phut}', (string) $minutes, (string) setting('otp_email_expiry', 'Mã có hiệu lực trong {phut} phút.')) }}</p>
                <p style="margin:0;font-size:14px;color:#46576b">{{ setting('otp_email_ignore', 'Nếu bạn không làm bài trắc nghiệm, hãy bỏ qua email này.') }}</p>
            </td></tr>
            <tr><td style="padding:16px 28px;border-top:1px solid #e3e7ec;font-size:12.5px;color:#7b8a9a">
                {{ setting('hotline') }}@if (filled(setting('hotline')) && filled(setting('email'))) · @endif{{ setting('email') }}
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
