<?php

namespace App\Support;

/**
 * Làm gì:   Danh mục quyền và ma trận vai trò MẶC ĐỊNH của CRM (nạp vào DB bởi migration create_crm_roles_tables).
 *           Sau khi nạp, quyền của từng vai trò sửa trên trang "Vai trò & phân quyền" (bảng crm_role_permission).
 * Ai gọi:   migration (nạp mặc định), RoleController (nhóm / nhãn quyền khi hiển thị ma trận).
 * Dữ liệu:  chỉ hằng số.
 *
 * Phạm vi dữ liệu (scope) của vai trò:
 *   all  — toàn bộ khách
 *   team — khách giao cho thành viên nhóm mình phụ trách + khách chưa phân công
 *   own  — chỉ khách được giao cho chính mình
 */
final class CrmPermissions
{
    /** key => [nhãn, nhóm] */
    public const PERMISSIONS = [
        'dashboard.view' => ['Xem Dashboard', 'Tổng quan'],
        'leads.view' => ['Xem khách hàng (theo phạm vi)', 'Khách hàng'],
        'leads.create' => ['Thêm khách hàng', 'Khách hàng'],
        'leads.update' => ['Chỉnh sửa khách hàng', 'Khách hàng'],
        'leads.delete' => ['Xóa khách hàng', 'Khách hàng'],
        'leads.assign' => ['Phân công tư vấn viên', 'Khách hàng'],
        'leads.export' => ['Xuất dữ liệu khách hàng', 'Khách hàng'],
        'activities.view' => ['Xem lịch sử tư vấn', 'Tư vấn'],
        'appointments.manage' => ['Đặt & cập nhật lịch hẹn', 'Tư vấn'],
        'notes.manage_all' => ['Sửa / xoá ghi chú của người khác', 'Tư vấn'],
        'sources.view' => ['Xem nguồn khách hàng', 'Nguồn & báo cáo'],
        'reports.view' => ['Xem báo cáo (theo phạm vi)', 'Nguồn & báo cáo'],
        'staff.manage' => ['Quản lý nhân viên', 'Hệ thống'],
        'roles.manage' => ['Vai trò & phân quyền', 'Hệ thống'],
        'settings.manage' => ['Cấu hình hệ thống', 'Hệ thống'],
    ];

    public const SCOPES = [
        'all' => 'Toàn bộ khách hàng',
        'team' => 'Khách của nhóm phụ trách',
        'own' => 'Chỉ khách được giao',
    ];

    /** Vai trò mặc định — thứ tự = cấp bậc (trên cao hơn dưới) */
    public const DEFAULT_ROLES = [
        'super_admin' => [
            'name' => 'Super Admin',
            'description' => 'Toàn quyền hệ thống. Không sửa được quyền của vai trò này.',
            'scope' => 'all',
            'system' => true,
            'permissions' => '*',
        ],
        'admin' => [
            'name' => 'Admin',
            'description' => 'Quản lý toàn bộ dữ liệu CRM, nhân viên và cấu hình.',
            'scope' => 'all',
            'system' => false,
            'permissions' => ['dashboard.view', 'leads.view', 'leads.create', 'leads.update', 'leads.delete', 'leads.assign', 'leads.export',
                'activities.view', 'appointments.manage', 'notes.manage_all', 'sources.view', 'reports.view', 'staff.manage', 'settings.manage'],
        ],
        'manager' => [
            'name' => 'Manager',
            'description' => 'Xem và quản lý khách hàng thuộc phạm vi nhóm phụ trách.',
            'scope' => 'team',
            'system' => false,
            'permissions' => ['dashboard.view', 'leads.view', 'leads.create', 'leads.update', 'leads.assign',
                'activities.view', 'appointments.manage', 'sources.view', 'reports.view', 'staff.manage'],
        ],
        'consultant' => [
            'name' => 'Consultant',
            'description' => 'Chỉ xem và quản lý khách hàng được phân công.',
            'scope' => 'own',
            'system' => false,
            'permissions' => ['dashboard.view', 'leads.view', 'leads.create', 'leads.update', 'activities.view', 'appointments.manage', 'reports.view'],
        ],
        'marketing' => [
            'name' => 'Marketing',
            'description' => 'Xem dữ liệu nguồn và báo cáo tổng hợp; không xem hồ sơ khách.',
            'scope' => 'all',
            'system' => false,
            'permissions' => ['dashboard.view', 'sources.view', 'reports.view'],
        ],
    ];

    /** Cấp bậc vai trò: số nhỏ = cao hơn. Vai trò ngoài danh sách (tự tạo) xếp dưới cùng. */
    public static function rank(string $role): int
    {
        $pos = array_search($role, array_keys(self::DEFAULT_ROLES), true);

        return $pos === false ? 99 : $pos;
    }
}
