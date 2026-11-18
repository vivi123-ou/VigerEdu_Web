<?php

/*
|--------------------------------------------------------------------------
| Gửi khách sang CRM (H5) – ánh xạ ô form → vị trí trong gói lead/2
|--------------------------------------------------------------------------
|
| Theo docs/HOP_DONG_KET_NOI.md mục 2.1 và docs/TU_DIEN_DU_LIEU_CHUNG.xlsx. Khóa bên trái là crm_key của ô form
| (sửa ở trang "Form khách điền thông tin"), bên phải là vị trí trong gói. Ô có crm_key không nằm ở đây, hoặc
| không có crm_key, đi vào extra_fields.{mã ô}. Chỉ gửi ô có giá trị và được đánh dấu gửi CRM (to_crm).
|
*/

return [
    'paths' => [
        'student_name' => 'contact.full_name',
        'role' => 'contact.role',
        'email' => 'contact.email',
        'phone' => 'contact.phone',
        'province' => 'contact.province',
        'birth_year' => 'contact.birth_year',
        // Ngày sinh: hợp đồng hiện chỉ có năm sinh (chỉ gửi năm cho tới khi sửa từ điển chung).
        'birth_date' => 'contact.birth_year',
        'parent_phone' => 'guardian.phone',
        'parent_name' => 'guardian.full_name',
        'current_school' => 'profile.current_school',
        'current_level' => 'profile.current_level',
    ],

    /** Loại hành vi nhận từ trình duyệt qua POST /t (các loại còn lại do máy chủ tự ghi). */
    'browser_events' => ['cta_call_clicked', 'cta_zalo_clicked'],
];
