<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ setting('email_subject', 'Kết quả trắc nghiệm Holland của bạn') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#12263f;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-top:4px solid {{ setting('primary_color', '#c1121f') }};">
                <tr>
                    <td style="padding:28px 28px 8px;">
                        <p style="margin:0 0 4px;font-size:13px;color:#6b7a8a;">{{ setting('site_name') }} · {{ setting('test_name') }}</p>
                        <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;">
                            {{ ed('email_greeting', 'Chào', 'Lời chào') }}{{ $result['name'] ? ' '.$result['name'] : '' }},
                        </h1>
                        <div style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#3d4f63;">{{ ed_rich('email_intro', 'Lời mở đầu') }}</div>

                        <p style="margin:0 0 6px;font-size:13px;text-transform:uppercase;letter-spacing:1px;color:#6b7a8a;">{{ setting('result_code_label', 'Mã Holland của bạn') }}</p>
                        <p style="margin:0 0 12px;font-size:34px;font-weight:bold;letter-spacing:6px;color:{{ setting('primary_color', '#c1121f') }};">{{ $result['code'] }}</p>
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
                            @foreach ($result['codeLetters'] as $letter)
                                <tr>
                                    <td style="padding:3px 10px 3px 0;font-weight:bold;vertical-align:top;">{{ $letter['letter'] }}</td>
                                    <td style="padding:3px 0;font-size:14px;line-height:1.5;"><strong>{{ $letter['name'] }}</strong> – {{ $letter['description'] }}</td>
                                </tr>
                            @endforeach
                        </table>

                        <p style="margin:0 0 24px;">
                            <a href="{{ $resultUrl }}" style="display:inline-block;background:{{ setting('primary_color', '#c1121f') }};color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 22px;">{{ ed('email_button_text', 'Xem đầy đủ kết quả', 'Chữ nút') }}</a>
                        </p>

                        <div style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#3d4f63;">{{ ed_rich('email_closing', 'Lời kết') }}</div>
                        <p style="margin:0 0 28px;font-size:15px;line-height:1.6;white-space:pre-line;">{{ ed('email_signature', setting('site_name'), 'Chữ ký') }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 28px;background:#faf6f6;font-size:12px;line-height:1.6;color:#6b7a8a;">
                        {{ setting('company_name', setting('site_name')) }}
                        @if (filled(setting('hotline'))) · Hotline: {{ setting('hotline') }} @endif
                        @if (filled(setting('email'))) · {{ setting('email') }} @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
