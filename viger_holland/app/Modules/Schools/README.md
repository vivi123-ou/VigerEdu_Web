# Module Schools (trường & vùng)

**Làm gì:** danh mục Quốc gia → Vùng (bang) → Trường để tư vấn viên tra nhanh. Thiết kế mở cho nhiều nước: loại hình, hình thức là dữ liệu; cột không có ô riêng lưu ở `extra`.

**Dữ liệu ban đầu** (`SchoolSeeder`, `database/seeders/data/schools/`): Đức, 16 bang (`docs/du_lieu_16_bang_duc.xlsx`, đã bỏ cột thống đốc và dấu chú thích "[8]"), 390 trường (`docs/du_lieu_390_truong_DH_da_phien_dich.xlsx`); 20 trường "ĐH Hành chính" / "ĐH Đặc thù" ẩn mặc định.

**Code:**
- `Models/Country.php`, `Region.php`, `School.php` (`School::IMPORTABLE`: các ô nhập được từ Excel).
- `Services/SchoolImport.php`: đọc tiêu đề cột, tự đoán ghép cột (tên cột Việt / Anh / Đức), thêm / cập nhật theo mã trường (không có mã thì theo tên), tự thêm vùng chưa có, không xóa trường nào.
- `Filament/Pages/SchoolsCatalog.php`: trang "Trường & vùng" (`/admin/truong-va-vung`). Xem: quyền `schools.view` (tư vấn viên, quản trị viên); sửa, thêm, "Nhập từ Excel" (2 bước: tải file → ghép cột): quyền `catalog.manage`.

**Chưa có:** gợi ý "trường có ngành này" (chờ file trường ↔ ngành do team điền).

**Test:** `tests/Feature/Schools/SchoolsCatalogTest.php`.
