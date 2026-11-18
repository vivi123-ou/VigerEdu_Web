<?php

namespace App\Modules\Respondent\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Lời đồng ý của khách: đúng câu khách thấy lúc bấm "Xem báo cáo" và thời điểm. Chỉ có khi admin bật câu đồng ý
 *           (cài đặt report_consent_text khác rỗng).
 * Ai gọi:   ReportAccessService; CrmSync (H5) đọc để gửi phần consent.
 * Dữ liệu:  bảng consents.
 */
#[Fillable(['respondent_id', 'test_session_id', 'version', 'text_snapshot', 'agreed_at'])]
class Consent extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['agreed_at' => 'datetime'];
    }
}
