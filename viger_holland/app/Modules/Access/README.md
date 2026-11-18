# Module Access (tài khoản, vai trò và quyền)

**Làm gì:** phân quyền trang quản trị bằng dữ liệu, không viết cứng, theo việc thật của từng người (Vân chốt 17/11):

- **Quản trị viên:** mọi việc. Là người duy nhất xem danh sách toàn bộ khách, số liệu làm bài và tải dữ liệu.
- **Tư vấn viên:** tra cứu từng khách, đọc báo cáo nội bộ để tư vấn.
- **Nhân viên** (gồm cả marketing): xem thông tin liên lạc của khách mới; sửa chữ, ảnh trên các trang khách xem.

Quản trị viên thêm được vai trò mới và tick việc được làm ở trang **Phân quyền**.

**Bảng:**
- `roles`: mã, tên, lõi, đang dùng;
- `permissions`: 14 quyền, mỗi quyền là một câu "Được…", có nhóm và dòng giải thích;
- `role_permission`;
- cột `users.role` lưu mã vai trò.

**Code:**
- `Permissions.php`: danh mục quyền (câu "Được…", 4 nhóm: có / không có thông tin cá nhân của khách, bài trắc nghiệm, quản lý hệ thống) và quyền mặc định của 3 vai trò;
- `Models/Role.php`, `Models/Permission.php`;
- `Policies/`:
  - `PermissionPolicy`: luật chung "có mã quyền thì được làm";
  - `UserPolicy`, `RolePolicy`: các chặn riêng;
- `Filament/Resources/Users`: trang **Tài khoản nhân sự** (`/admin/users`), có nút Khóa / Mở khóa;
- `Filament/Resources/Roles`: trang **Phân quyền** (`/admin/roles`), quyền chia nhóm thành các ô tick;
- `App\Models\User`: `hasPermission()`, `canAccessAdmin()`, `canAccessPanel()`, `canAccessMenu()`, `canExportData()`.

Menu, tên trang, hướng dẫn nút "?" đều ở `config/admin_menu.php`; mỗi mục ghi mã quyền cần có.

**Quy tắc:**
- Quản trị viên luôn có mọi quyền. Vai trò Quản trị viên không sửa được.
- **Chỉ quản trị viên tải dữ liệu** (`data.export`): không tick được cho vai trò khác, tick nhầm trong cơ sở dữ liệu cũng không có tác dụng.
- Không ai tự đổi vai trò, tự khóa hay tự xóa mình. Người không phải quản trị viên không sửa được vai trò của chính mình, cũng không sửa được tài khoản quản trị viên.
- Vai trò lõi, hoặc vai trò còn tài khoản, thì không xóa được. Vai trò bị tắt thì tài khoản có vai trò đó bị đăng xuất.
- Trang không có quyền báo **404 (không tìm thấy)** thay vì 403, và trang của vai trò bị giới hạn không ghi ra giới hạn của họ.

**Dữ liệu ban đầu:** `RoleSeeder`. Chữ của quyền cập nhật theo code; quyền đã tick của vai trò thì giữ nguyên.

**Test:** `tests/Feature/Access/RolePermissionTest.php`, `AccountManagementTest.php`.
