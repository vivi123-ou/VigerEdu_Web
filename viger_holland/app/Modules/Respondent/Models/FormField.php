<?php

namespace App\Modules\Respondent\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Làm gì:   Một ô trên form "Cập nhật thông tin để xem báo cáo". Admin thêm, sửa, ẩn, đổi bắt buộc, đổi tên trường bên CRM.
 * Ai gọi:   ReportForm (H4) dựng form và kiểm tra dữ liệu; CrmSync (H5) đóng gói theo crm_key; FormFieldSeeder.
 * Dữ liệu:  bảng form_fields. Không chứa dữ liệu khách (giá trị khách điền nằm ở field_values).
 */
#[Fillable(['key', 'crm_key', 'label', 'type', 'options', 'options_source', 'conditions', 'group', 'help', 'placeholder', 'is_required', 'to_crm', 'on_report', 'is_visible', 'is_system', 'sort_order'])]
class FormField extends Model
{
    use SoftDeletes;

    /** @var array<string, string> */
    public const TYPES = [
        'text' => 'Chữ ngắn',
        'textarea' => 'Đoạn văn',
        'phone' => 'Số điện thoại',
        'email' => 'Email',
        'year' => 'Năm',
        'number' => 'Số (ví dụ điểm 0–10)',
        'select' => 'Chọn một trong danh sách thả xuống',
        'radio' => 'Chọn một (nút tròn)',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'conditions' => 'array',
            'is_required' => 'boolean',
            'to_crm' => 'boolean',
            'on_report' => 'boolean',
            'is_visible' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    /**
     * @param  Builder<FormField>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
