<?php

namespace Database\Seeders;

use App\Modules\Respondent\Models\FormField;
use Illuminate\Database\Seeder;

/**
 * Làm gì:   Nạp 7 ô của form "Cập nhật thông tin để xem báo cáo" (chốt 14/11, theo mẫu UEH) kèm tên trường bên CRM.
 *           Học lực, ngoại ngữ, điểm môn, ngân sách: CRM thu sau, không hỏi ở đây.
 * Ai gọi:   DatabaseSeeder. Chạy lại không trùng; ô đã có thì không ghi đè (admin có thể đã sửa).
 * Dữ liệu:  bảng form_fields.
 */
class FormFieldSeeder extends Seeder
{
    /** 34 tỉnh, thành phố sau sắp xếp 2025 (cần Vân kiểm lại; admin sửa được). */
    private const PROVINCES = [
        'Hà Nội', 'TP. Hồ Chí Minh', 'Hải Phòng', 'Đà Nẵng', 'Cần Thơ', 'Huế',
        'An Giang', 'Bắc Ninh', 'Cà Mau', 'Cao Bằng', 'Đắk Lắk', 'Điện Biên', 'Đồng Nai', 'Đồng Tháp', 'Gia Lai',
        'Hà Tĩnh', 'Hưng Yên', 'Khánh Hòa', 'Lai Châu', 'Lâm Đồng', 'Lạng Sơn', 'Lào Cai', 'Nghệ An', 'Ninh Bình',
        'Phú Thọ', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị', 'Sơn La', 'Tây Ninh', 'Thái Nguyên', 'Thanh Hóa',
        'Tuyên Quang', 'Vĩnh Long',
    ];

    public function run(): void
    {
        $choices = fn (array $values): array => array_map(fn ($value, $label) => ['value' => $value, 'label' => $label], array_keys($values), $values);

        $fields = [
            ['key' => 'ho_ten', 'crm_key' => 'student_name', 'label' => 'Họ tên', 'type' => 'text', 'is_required' => true, 'is_system' => true],
            ['key' => 'nam_sinh', 'crm_key' => 'birth_year', 'label' => 'Năm sinh của học sinh', 'type' => 'year', 'is_required' => true, 'is_system' => true,
                'help' => 'Dùng để biết lớp và kiểm tra cần phụ huynh đồng ý khi dưới 18 tuổi.'],
            ['key' => 'email', 'crm_key' => 'email', 'label' => 'Email', 'type' => 'email', 'is_required' => true, 'is_system' => true,
                'help' => 'Nhận mã xác nhận để mở báo cáo.'],
            ['key' => 'so_dien_thoai', 'crm_key' => 'phone', 'label' => 'Điện thoại / Zalo', 'type' => 'phone', 'is_required' => true, 'is_system' => true],
            ['key' => 'truong', 'crm_key' => 'current_school', 'label' => 'Trường', 'type' => 'text', 'placeholder' => 'Nhập tên trường'],
            ['key' => 'tinh', 'crm_key' => 'province', 'label' => 'Tỉnh / thành phố', 'type' => 'select',
                'options' => $choices(array_combine(self::PROVINCES, self::PROVINCES)), 'placeholder' => 'Chọn tỉnh / thành phố'],
            ['key' => 'doi_tuong', 'crm_key' => 'role', 'label' => 'Thuộc đối tượng', 'type' => 'radio', 'is_required' => true, 'is_system' => true,
                'options' => $choices(['HS' => 'Học sinh / sinh viên', 'PH' => 'Phụ huynh', 'KHAC' => 'Khác']), 'options_source' => 'role'],
        ];

        foreach ($fields as $index => $field) {
            FormField::withTrashed()->firstOrCreate(['key' => $field['key']], $field + [
                'to_crm' => true, 'on_report' => false, 'is_visible' => true, 'sort_order' => $index + 1,
            ]);
        }
    }
}
