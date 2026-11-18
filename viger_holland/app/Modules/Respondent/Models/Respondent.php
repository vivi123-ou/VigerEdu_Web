<?php

namespace App\Modules\Respondent\Models;

use App\Modules\Assessment\Models\TestSession;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Làm gì:   Một người đã điền form và nhập đúng mã email. Cùng một email làm bài nhiều lần thì dùng chung một hồ sơ.
 *           Họ tên, email, số điện thoại lưu mã hóa; cột *_hash để tìm (tra cứu báo cáo, gộp khách).
 * Ai gọi:   ReportAccessService (tạo / cập nhật sau khi xác nhận mã), tra cứu báo cáo (H4b), CrmSync (H5).
 * Dữ liệu:  bảng respondents. Có dữ liệu cá nhân: không ghi vào log.
 */
#[Fillable(['uuid', 'full_name', 'email', 'email_hash', 'phone', 'phone_hash', 'parent_phone', 'parent_phone_hash', 'birth_year', 'role', 'email_verified_at'])]
#[Hidden(['full_name', 'email', 'phone', 'parent_phone'])]
class Respondent extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'full_name' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'parent_phone' => 'encrypted',
            'birth_year' => 'integer',
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * Băm để tìm: chuẩn hóa trước (email chữ thường; SĐT về dạng 0xxxxxxxxx) rồi HMAC bằng khóa ứng dụng.
     */
    public static function hashOf(?string $value): ?string
    {
        return filled($value) ? hash_hmac('sha256', $value, (string) config('app.key')) : null;
    }

    /**
     * @return HasMany<TestSession, $this>
     */
    public function testSessions(): HasMany
    {
        return $this->hasMany(TestSession::class);
    }

    /**
     * @return HasMany<Consent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }
}
