<?php

namespace App\Modules\Access;

/**
 * Làm gì:   Danh mục quyền của trang quản trị Holland và quyền mặc định của các vai trò có sẵn.
 * Ai gọi:   RoleSeeder (nạp vào bảng), config/admin_menu.php (mỗi mục menu ghi một mã quyền).
 * Dữ liệu:  chỉ là hằng số; quyền thật của từng vai trò nằm trong bảng role_permission và sửa được.
 */
final class Permissions
{
    /**
     * Mã quyền => [tên, nhóm].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const ALL = [
        'dashboard.view' => ['Xem tổng quan', 'Chung'],
        'attempts.view' => ['Xem lượt làm bài và báo cáo nội bộ', 'Khách hàng'],
        'questions.manage' => ['Quản lý thư viện câu hỏi', 'Bài trắc nghiệm'],
        'test_settings.manage' => ['Sửa cài đặt bài test', 'Bài trắc nghiệm'],
        'catalog.manage' => ['Quản lý danh mục nhóm, cặp, 120 mã, ngành, nghề', 'Danh mục'],
        'form_fields.manage' => ['Quản lý trường thông tin khách', 'Khách hàng'],
        'content.manage' => ['Sửa nội dung trang khách và thư viện ảnh', 'Nội dung trang'],
        'appearance.manage' => ['Sửa giao diện, chân trang, cài đặt chung', 'Hệ thống'],
        'crm.manage' => ['Cấu hình kết nối CRM', 'Hệ thống'],
        'accounts.manage' => ['Quản lý tài khoản và phân quyền', 'Hệ thống'],
        'logs.view' => ['Xem nhật ký', 'Hệ thống'],
        'data.export' => ['Xuất Excel, tải dữ liệu, sao lưu', 'Hệ thống'],
    ];

    /**
     * Vai trò có sẵn: mã => [tên, mô tả, quyền ('*' = tất cả), vai trò lõi?].
     * Thiết kế tổng thể 14/11: Quản trị viên > Tư vấn viên > Nhân viên; chăm sóc khách làm ở CRM.
     *
     * @var array<string, array{0: string, 1: string, 2: list<string>, 3: bool}>
     */
    public const DEFAULT_ROLES = [
        'ADMIN' => ['Quản trị viên', 'Toàn quyền; người duy nhất được xuất dữ liệu.', ['*'], true],
        'COUNSELOR' => ['Tư vấn viên', 'Xem lượt làm bài và báo cáo nội bộ đầy đủ.', ['dashboard.view', 'attempts.view'], true],
        'STAFF' => ['Nhân viên', 'Làm việc chủ yếu ở CRM; ở Holland chỉ xem tổng quan.', ['dashboard.view'], true],
        'EDITOR' => ['Marketing', 'Sửa nội dung trang khách và thư viện ảnh.', ['dashboard.view', 'content.manage'], false],
    ];
}
