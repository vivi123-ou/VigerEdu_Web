<?php

namespace App\Modules\Respondent\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Làm gì:   Giá trị khách điền cho một ô thông tin; chỉ lưu ô có giá trị (ô trống không lưu, không gửi CRM).
 * Ai gọi:   ReportForm (H4) ghi; CrmSync (H5), báo cáo nội bộ đọc.
 * Dữ liệu:  bảng field_values (có dữ liệu cá nhân: giá trị lưu mã hóa, không ghi vào log).
 */
#[Fillable(['test_session_id', 'form_field_id', 'key', 'value'])]
class FieldValue extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'encrypted'];
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class)->withTrashed();
    }
}
