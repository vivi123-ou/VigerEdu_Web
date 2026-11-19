<?php

namespace App\Modules\Access;

/**
 * Làm gì:   Danh mục quyền của trang quản trị Holland (viết thành câu "Được…" cho người không làm IT)
 *           và quyền mặc định của các vai trò có sẵn. Ai đăng nhập được cũng thấy Trang chủ quản trị, không cần quyền riêng.
 * Ai gọi:   RoleSeeder (nạp vào bảng), các Policy, trang Filament (mỗi trang kiểm tra một mã quyền), trang Phân quyền.
 * Dữ liệu:  chỉ là hằng số; quyền thật của từng vai trò nằm trong bảng role_permission và sửa được.
 */
final class Permissions
{
    /** Quyền xuất dữ liệu: chỉ quản trị viên (Vân chốt 14/11), không cấp được cho vai trò khác. */
    public const EXPORT = 'data.export';

    public const GROUP_PERSONAL = 'Có thông tin cá nhân của khách';

    public const GROUP_GENERAL = 'Không có thông tin cá nhân';

    public const GROUP_TEST = 'Bài trắc nghiệm';

    public const GROUP_SYSTEM = 'Quản lý hệ thống';

    /**
     * Mã quyền => [câu "Được…", nhóm, giải thích thêm]. Thứ tự ở đây là thứ tự hiện trên trang Phân quyền.
     *
     * @var array<string, array{0: string, 1: string, 2: string|null}>
     */
    public const ALL = [
        'reports.lookup' => ['Được tra cứu và đọc báo cáo của từng khách', self::GROUP_PERSONAL,
            'Tìm theo số điện thoại, email hoặc mã báo cáo; đọc báo cáo nội bộ đầy đủ của khách đó.'],
        'contacts.view' => ['Được xem thông tin liên lạc của khách mới', self::GROUP_PERSONAL,
            'Họ tên, điện thoại / Zalo, email, đối tượng, tỉnh của khách vừa để lại thông tin. Không thấy kết quả bài làm.'],
        'customers.list' => ['Được xem danh sách tất cả khách đã làm bài', self::GROUP_PERSONAL,
            'Toàn bộ khách, mọi thời điểm, kèm kết quả. Nên chỉ để quản trị viên.'],
        self::EXPORT => ['Được tải dữ liệu khách ra Excel, sao lưu', self::GROUP_PERSONAL,
            'Chỉ quản trị viên, không cấp được cho vai trò khác.'],
        'schools.view' => ['Được xem danh sách trường và vùng (bang)', self::GROUP_GENERAL,
            'Danh mục trường đại học, bang ở Đức để tư vấn. Sửa và nhập Excel cần quyền sửa danh mục.'],
        'stats.view' => ['Được xem số liệu làm bài', self::GROUP_GENERAL,
            'Bao nhiêu người làm bài, đến từ đâu, bỏ ngang ở đâu, mã nào phổ biến. Không có tên khách.'],
        'content.manage' => ['Được sửa chữ, ảnh trên các trang khách xem', self::GROUP_GENERAL,
            'Trang mở đầu, chữ trên báo cáo và email, ô giới thiệu trên viger.edu.vn, kho ảnh.'],
        'appearance.manage' => ['Được đổi logo, màu, hotline, chân trang', self::GROUP_GENERAL,
            'Ảnh hưởng mọi trang khách xem.'],
        'questions.manage' => ['Được sửa, tạm ngưng, thêm câu hỏi trắc nghiệm', self::GROUP_TEST, null],
        'test_settings.manage' => ['Được đổi cách bốc đề và chấm điểm', self::GROUP_TEST,
            'Số câu, khi nào khách thấy mã 3 chữ, số ngành gợi ý.'],
        'catalog.manage' => ['Được sửa giải thích mã Holland, ngành học, nghề', self::GROUP_TEST, null],
        'form_fields.manage' => ['Được sửa form thông tin khách điền', self::GROUP_TEST,
            'Ô nào bắt buộc, ô nào gửi sang CRM.'],
        'crm.manage' => ['Được cài đặt gửi khách sang CRM', self::GROUP_SYSTEM, null],
        'accounts.manage' => ['Được thêm tài khoản nhân sự và phân quyền', self::GROUP_SYSTEM,
            'Không sửa được tài khoản quản trị viên.'],
        'api.manage' => ['Được cấp và thu hồi mã kết nối API (chatbot, CRM)', self::GROUP_SYSTEM,
            'Ứng dụng có mã đọc được kết quả Holland (không có họ tên, liên lạc).'],
        'logs.view' => ['Được xem lịch sử thao tác', self::GROUP_SYSTEM,
            'Ai sửa gì, ai xem báo cáo của khách nào, lúc nào.'],
        'system.manage' => ['Được đổi cài đặt hệ thống và chặn / bỏ chặn địa chỉ IP', self::GROUP_SYSTEM,
            'Nhật ký tự dọn sau bao lâu, sao lưu giữ bao lâu, các địa chỉ IP bị chặn vì truy cập bất thường.'],
    ];

    /**
     * Vai trò có sẵn: mã => [tên, mô tả, quyền ('*' = tất cả), vai trò lõi?].
     * Thứ bậc (Thầy chốt 18/11): Quản trị viên > Nhân viên > Tư vấn viên; chăm sóc khách làm ở CRM.
     * Vân chốt 17/11: không có vai trò Marketing riêng (marketing là Nhân viên); tư vấn viên chỉ tra cứu từng khách;
     * nhân viên chỉ xem thông tin liên lạc, không xem kết quả hay số liệu.
     *
     * @var array<string, array{0: string, 1: string, 2: list<string>, 3: bool}>
     */
    public const DEFAULT_ROLES = [
        'ADMIN' => ['Quản trị viên', 'Toàn quyền; người duy nhất được xem toàn bộ khách và tải dữ liệu.', ['*'], true],
        'STAFF' => ['Nhân viên', 'Liên lạc với khách mới; sửa chữ, ảnh trên trang khách (gồm việc marketing).', ['contacts.view', 'content.manage'], true],
        'COUNSELOR' => ['Tư vấn viên', 'Tra cứu từng khách và đọc báo cáo nội bộ để tư vấn; xem danh sách trường.', ['reports.lookup', 'schools.view'], true],
    ];

    /**
     * Các nhóm quyền theo thứ tự hiện trên trang Phân quyền.
     *
     * @return list<string>
     */
    public static function groups(): array
    {
        return [self::GROUP_PERSONAL, self::GROUP_GENERAL, self::GROUP_TEST, self::GROUP_SYSTEM];
    }
}
