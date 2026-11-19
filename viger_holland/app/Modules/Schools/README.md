# Module Schools (trường & vùng)

**Làm gì:** danh mục Quốc gia → Vùng (bang) → Trường để tư vấn viên tra nhanh. Thiết kế mở cho nhiều nước: loại hình, hình thức là dữ liệu; cột không có ô riêng lưu ở `extra`.

**Dữ liệu ban đầu** (`SchoolSeeder`, `database/seeders/data/schools/`): Đức, 16 bang (`docs/du_lieu_16_bang_duc.xlsx`, đã bỏ cột thống đốc và dấu chú thích "[8]"), 390 trường (`docs/du_lieu_390_truong_DH_da_phien_dich.xlsx`); 20 trường "ĐH Hành chính" / "ĐH Đặc thù" ẩn mặc định.

**Code:**
- `Models/Country.php`, `Region.php`, `School.php` (`School::IMPORTABLE`: các ô nhập được từ Excel).
- `Models/SchoolProgram.php`: trường ↔ ngành (bảng `school_programs`, mỗi bậc một dòng).
- `Services/SchoolImport.php`, `Services/ProgramImport.php`: nhập Excel trên khung chung `App\Support\SpreadsheetImport` (đọc tiêu đề, tự đoán ghép cột Việt / Anh / Đức, file mẫu, báo dòng lỗi); thêm / cập nhật, không xóa, không tạo trùng. Nút dùng chung: `App\Filament\Support\ImportActions`.
- `Filament/Pages/SchoolsCatalog.php`: trang "Trường & vùng" (`/admin/truong-va-vung`). Xem: quyền `schools.view` (tư vấn viên, quản trị viên); sửa, thêm, "Nhập từ Excel" (2 bước: tải file → ghép cột): quyền `catalog.manage`.

**Trường có ngành này:** thẻ "Ngành của trường" (lọc theo ngành / trường / bang; quản trị viên thêm, sửa, nhập Excel); báo cáo nội bộ hiện trường có ngành gợi ý; API `catalog/schools`, `catalog/programs`. Chỉ quản trị viên và tư vấn viên thấy, khách không thấy. Dữ liệu: chờ team điền file trường – ngành ("Tải file mẫu").

**Test:** `tests/Feature/Schools/SchoolsCatalogTest.php`, `SchoolProgramsTest.php`.
