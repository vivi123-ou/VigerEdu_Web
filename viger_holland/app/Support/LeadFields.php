<?php

namespace App\Support;

/**
 * Trường chuẩn của gói `lead/1` (docs/HOP_DONG_KET_NOI.md mục 3.1, tên theo docs/TU_DIEN_DU_LIEU_CHUNG.xlsx).
 * Dùng cho ô "Gửi sang CRM vào trường…" của câu Phần A.
 */
final class LeadFields
{
    /**
     * Trường chuẩn => tên tiếng Việt (theo từ điển dữ liệu chung).
     *
     * @var array<string, string>
     */
    public const LABELS = [
        'role' => 'Vai trò (học sinh / phụ huynh)',
        'student_name' => 'Họ tên học sinh',
        'birth_year' => 'Năm sinh',
        'phone' => 'Số điện thoại',
        'email' => 'Email',
        'parent_name' => 'Họ tên phụ huynh',
        'parent_phone' => 'SĐT phụ huynh',
        'province' => 'Tỉnh / Thành phố',
        'current_level' => 'Trình độ hiện tại',
        'current_school' => 'Trường đang học',
        'gpa' => 'Điểm trung bình (GPA)',
        'english_level' => 'Tiếng Anh',
        'german_level' => 'Tiếng Đức',
        'desired_level' => 'Bậc muốn học',
        'country' => 'Quốc gia',
        'region' => 'Khu vực mong muốn',
        'interested_major' => 'Ngành quan tâm',
        'budget' => 'Ngân sách / năm',
        'timing' => 'Thời điểm dự định',
        'consent' => 'Đồng ý lưu thông tin (bắt buộc)',
    ];

    /**
     * Trường luôn có mặt trong gói lead/1 (null nếu khách không trả lời). consent gửi riêng.
     *
     * @return list<string>
     */
    public static function standard(): array
    {
        return [...array_keys(array_diff_key(self::LABELS, ['consent' => true])), 'appointment_at'];
    }

    /**
     * Trường chọn được trên admin.
     *
     * @return list<string>
     */
    public static function selectable(): array
    {
        return array_keys(self::LABELS);
    }

    public static function label(string $field): string
    {
        return self::LABELS[$field] ?? $field;
    }

    /**
     * Mã trường tùy biến hợp lệ (gửi trong extra_fields): chữ thường không dấu, số, gạch dưới.
     */
    public static function isValidExtraKey(string $key): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]{1,49}$/', $key);
    }
}
