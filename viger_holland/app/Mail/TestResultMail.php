<?php

namespace App\Mail;

use App\Models\Attempt;
use App\Services\ResultPresenter;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Email gửi khách sau khi nộp bài: mã Holland, link xem lại kết quả, lời hẹn tư vấn.
 * Tiêu đề / lời mở đầu / chữ ký lấy từ Cài đặt (admin sửa được).
 */
class TestResultMail extends Mailable
{
    public function __construct(public readonly Attempt $attempt) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: (string) setting('email_subject', 'Kết quả trắc nghiệm Holland của bạn'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test-result',
            with: [
                'result' => app(ResultPresenter::class)->present($this->attempt),
                'resultUrl' => route('result.show', $this->attempt->result_token),
            ],
        );
    }
}
