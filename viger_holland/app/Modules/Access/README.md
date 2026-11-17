# Module Access (tài khoản, vai trò và quyền)

**Làm gì:** phân quyền trang quản trị bằng dữ liệu, không viết cứng. Có 3 vai trò sẵn: Quản trị viên > Tư vấn viên > Nhân viên. Nhân viên gồm cả việc marketing (Vân chốt 17/11). Quản trị viên thêm được vai trò mới và tick quyền cho từng vai trò ngay trên trang quản trị.

**Bảng:** `roles` (mã, tên, lõi, đang dùng), `permissions` (12 mã quyền), `role_permission`. Cột `users.role` lưu mã vai trò.

**Code:**
- `Permissions.php`: danh mục mã quyền và quyền mặc định của 3 vai trò (chỉ dùng khi nạp lần đầu);
- `Models/Role.php`, `Models/Permission.php`;
- `Policies/`:
  - `PermissionPolicy`: luật chung "có mã quyền thì được làm";
  - `UserPolicy`, `RolePolicy`: các chặn riêng;
  - Filament tự hỏi các Policy này trước khi hiện menu, nút, trang;
- `Filament/Resources/Users`: trang **Tài khoản**, `/admin/users`;
- `Filament/Resources/Roles`: trang **Vai trò & quyền**, `/admin/roles`;
- `App\Models\User`: `hasPermission()`, `canAccessAdmin()`, `canAccessPanel()`, `canExportData()`.

**Quy tắc:**
- Quản trị viên luôn có mọi quyền. Vai trò Quản trị viên không sửa được.
- **Chỉ quản trị viên xuất dữ liệu** (`data.export`). Quyền này không tick được cho vai trò khác, và có tick nhầm trong cơ sở dữ liệu cũng không có tác dụng.
- Chỉ quản trị viên tạo hoặc sửa được tài khoản quản trị viên. Không ai tự đổi vai trò, tự khóa hay tự xóa tài khoản của mình.
- Vai trò lõi, hoặc vai trò còn tài khoản, thì không xóa được. Vai trò bị tắt thì tài khoản có vai trò đó bị đăng xuất.

**Dữ liệu ban đầu:** `RoleSeeder` (chạy lại không ghi đè quyền admin đã sửa).

**Test:** `tests/Feature/Access/RolePermissionTest.php`, `AccountManagementTest.php`.
