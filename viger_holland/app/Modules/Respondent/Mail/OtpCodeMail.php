<?php

namespace App\Modules\Respondent\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Làm gì:   Email chứa mã xác nhận 6 số để mở báo cáo đầy đủ. Chỉ có mã, không có link báo cáo (Vân chốt 17/11).
 *           Tiêu đề và các câu lấy từ cài đặt (otp_email_*), sửa ở "Chữ trên báo cáo & email".
 * Ai gọi:   OtpService::issue() (gửi ngay, không qua hàng đợi, để khách nhận mã liền).
 * Dữ liệu:  mã và số phút hiệu lực; không chứa họ tên.
 */
class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: str_replace('{ma}', $this->code, (string) setting('otp_email_subject', 'Mã xác nhận của bạn: {ma}')));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp-code');
    }
}
