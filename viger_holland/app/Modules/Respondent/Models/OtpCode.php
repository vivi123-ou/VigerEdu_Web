<?php

namespace App\Modules\Respondent\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Một mã xác nhận gửi qua email cho một lượt làm bài. Chỉ lưu HMAC của mã, không lưu mã gốc.
 * Ai gọi:   OtpService.
 * Dữ liệu:  bảng otp_codes (không có email gốc, chỉ email_hash).
 */
#[Fillable(['test_session_id', 'email_hash', 'code_hash', 'expires_at', 'attempts', 'locked_until', 'used_at'])]
class OtpCode extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'locked_until' => 'datetime',
            'used_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
