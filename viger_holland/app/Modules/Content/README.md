# Module Content (trang khách xem)

**Làm gì:** các trang quản trị sửa nội dung khách thấy, trên Filament: Trang mở đầu, Chữ trên báo cáo & email, Ô giới thiệu trên viger.edu.vn, Kho ảnh, Logo / màu / hotline / chân trang, Hiển thị trên Google.

**Thêm ô mới:** thêm một dòng ở `config/admin_content.php` (khóa, nhãn, kiểu, gợi ý). Không phải sửa trang hay view.
Kiểu ô: `text`, `textarea`, `rich`, `image` (chọn trong Kho ảnh hoặc tải mới tại chỗ), `color`, `font`, `url` (tự thêm `https://`), `email`, `links`, `domains`.

**Code:**
- `app/Filament/Pages/ContentSettingsPage.php`: nền chung: form theo phần, "Lưu", "Xem trước" (mở thẻ mới, chưa lưu), bảng "Lịch sử thay đổi" có "Quay lại bản này".
- `Filament/Pages/`:
  - `IntroPage` (`/admin/trang-mo-dau`): thẻ Banner + thẻ Các khối nội dung (Builder: thêm, kéo thả thứ tự, bật / tắt, xóa); nút "Sửa trực tiếp trên trang";
  - `ReportTexts` (`/admin/chu-tren-bao-cao`), `EmbedBox` (`/admin/o-gioi-thieu`, quản trị viên thấy thêm đoạn mã cho web chính), `Appearance` (`/admin/logo-mau-hotline`), `SeoSettings` (`/admin/hien-thi-tren-google`);
  - `MediaLibrary` (`/admin/kho-anh`): tải nhiều ảnh, lưới ảnh, chép link, thùng rác.
- `app/Filament/Support/ContentFields.php`: dựng ô Filament theo kiểu, ô "Chọn ảnh".
- Kiểm tra và chuẩn hóa: `App\Support\ContentPages` (báo lỗi tiếng Việt), `App\Support\IntroBlocks::normalize` (khối).
- Xem trước: `App\Support\ContentPreview` (cache 10 phút, chỉ người bấm mở được) + `ContentPreviewController` (`/admin/xem-truoc/{mã}`).
- Quay lại bản cũ: `App\Support\HistoryReverter` (nhân viên chỉ quay lại nội dung trang mình được vào).

**Giữ ngoài Filament (có lý do):** "Sửa trực tiếp trên trang" (`PageEditorController`, trang khách ở chế độ sửa) và các lệnh nó gọi: lưu, ẩn / hiện khối, đổi thứ tự (`IntroBlockController`), hộp chọn ảnh (`MediaController`, JSON).

**Quyền:** `content.manage` (Nhân viên, Quản trị viên): Trang mở đầu, Chữ trên báo cáo, Ô giới thiệu, Kho ảnh. `appearance.manage` (Quản trị viên): Logo…, Hiển thị trên Google. Không có quyền: báo 404.

**Test:** `tests/Feature/Admin/ContentPagesTest.php`, `PageEditorTest.php`, `tests/Feature/EmbedAndRolesTest.php`.
