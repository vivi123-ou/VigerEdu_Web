# Module Access (vai trò và quyền)

**Làm gì:** phân quyền trang quản trị bằng dữ liệu, không viết cứng. Vai trò có sẵn: Quản trị viên > Tư vấn viên > Nhân viên, thêm vai trò Marketing (xóa được). Quản trị viên thêm được vai trò mới và tick quyền cho từng vai trò (trang quản lý làm ở H6).

**Bảng:** `roles` (mã, tên, lõi, đang dùng), `permissions` (12 mã quyền), `role_permission`. Cột `users.role` lưu mã vai trò.

**Code:**
- `Permissions.php`: danh mục mã quyền và quyền mặc định của 4 vai trò (chỉ dùng khi nạp lần đầu);
- `Models/Role.php`, `Models/Permission.php`;
- `App\Models\User`: `hasPermission()`, `canAccessAdmin()`, `canAccessMenu()`, `canExportData()`.

Mỗi mục trong `config/admin_menu.php` ghi một mã quyền (`'permission'`). Middleware `menu:<mục>` chặn khi gõ thẳng địa chỉ (403).

**Quy tắc:** quản trị viên luôn có mọi quyền; **chỉ quản trị viên xuất dữ liệu** (`data.export`). Vai trò bị tắt thì tài khoản không vào được admin.

**Dữ liệu ban đầu:** `RoleSeeder` (chạy lại không ghi đè quyền admin đã sửa). **Test:** `tests/Feature/Access/RolePermissionTest.php`.
