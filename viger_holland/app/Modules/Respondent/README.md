# Module Respondent (thông tin khách, mã xác nhận, báo cáo đầy đủ)

**Làm gì:** sau kết quả sơ lược, khách điền form → nhận mã 6 số qua email → xem báo cáo đầy đủ **trong thẻ (tab) đó**: tải lại vẫn còn, đóng thẻ là mất (Vân chốt 18/11). Thẻ nhận "chìa khóa xem" lưu trong `sessionStorage` (trình duyệt tự xóa khi đóng thẻ); máy chủ chỉ giữ băm của chìa, làm bài lượt mới thì chìa cũ hết tác dụng. Muốn xem lại thì làm bài lại. Không email link, không PDF, không chia sẻ, không hiện họ tên.

**Luồng và đường dẫn** (`Http/Controllers/ReportController.php`, đều `noindex`, không có mã trên link):

| Đường dẫn | Việc | Giới hạn / phút / IP |
|---|---|---|
| GET `/nhan-bao-cao` | Form (chỉ mở khi đã làm xong bài) | |
| POST `/nhan-bao-cao` | Kiểm tra, Turnstile, lưu giá trị, gửi mã | 5 (`report-form`) |
| GET `/xac-nhan` | Nhập mã, email che bớt, "Gửi lại mã", "Sửa email" | |
| POST `/xac-nhan` | Kiểm tra mã → tạo / cập nhật hồ sơ → báo cáo | 10 (`report-code`) |
| POST `/xac-nhan/gui-lai` | Gửi lại mã | 5 (`report-resend`) |
| GET `/bao-cao` | Báo cáo đầy đủ (lần đầu); tải lại thì `khach.js` lấy lại bằng chìa khóa của thẻ | |
| POST `/bao-cao/xem` | Trả thân báo cáo khi đúng chìa khóa, sai → 404 ("Báo cáo đã đóng") | 30 (`report-view`) |

Mỗi việc một bộ đếm riêng (`AppServiceProvider`). Viết `throttle:5,1` thì mọi route dùng chung một bộ đếm, khách làm xong 6 trang bài sẽ bị chặn ở form.

**Bảng:**
- `form_fields`: các ô của form; `crm_key` theo `docs/TU_DIEN_DU_LIEU_CHUNG.xlsx`; `conditions` để ẩn / đổi chữ theo ô khác, ví dụ `{"hide_if":{"doi_tuong":["PH"]}}`, `{"label_if":{"doi_tuong":{"PH":"Email của học sinh"}}}`;
- `field_values`: giá trị khách điền, **mã hóa**, gắn với lượt làm bài;
- `respondents`: hồ sơ khách tạo **sau khi** nhập đúng mã, gộp theo email. Họ tên, email, SĐT, SĐT phụ huynh, ngày sinh **mã hóa**; `email_hash`, `phone_hash`, `parent_phone_hash` (HMAC) để tra cứu;
- `otp_codes`: chỉ lưu HMAC của mã và băm email;
- `consents`: chỉ có khi admin gõ câu đồng ý (cài đặt `report_consent_text`, mặc định trống).

**8 ô mặc định** (chốt 14/11, thêm 18/11):
- bắt buộc: Họ tên, Ngày sinh của học sinh (chọn trên lịch có sẵn của trình duyệt, `crm_key` = `birth_date`; CRM nhận `birth_year` tách từ ngày này), Email, Điện thoại / Zalo của phụ huynh (`parent_phone`), Thuộc đối tượng (`HS` / `PH` / `KHAC`);
- không bắt buộc: Điện thoại / Zalo của học sinh (ẩn khi chọn Phụ huynh), Trường, Tỉnh / thành phố.

**Code:**
- `Services/ReportForm.php`: dựng form từ `form_fields`, luật kiểm tra, chuẩn hóa SĐT (`+84` → `0`), lưu / đọc giá trị;
- `Services/OtpService.php`: tạo, gửi (gửi ngay, không qua hàng đợi để khách nhận liền), kiểm tra mã. Con số trong cài đặt: hiệu lực `otp_ttl_minutes` (5), nhập sai `otp_max_attempts` (5) thì khóa `otp_lock_minutes` (30) **theo email**, chờ gửi lại `otp_resend_seconds` (60), tối đa `otp_max_per_hour` (5) mã / giờ / email;
- `Services/ReportAccessService.php`: tạo / cập nhật hồ sơ, ghi lời đồng ý, đánh dấu lượt đã xác nhận;
- `Services/Turnstile.php`: Cloudflare Turnstile chạy ẩn; khóa ở `.env` (`TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`), để trống thì bỏ qua;
- `Mail/OtpCodeMail.php` + `resources/views/emails/otp-code.blade.php`: email chỉ có mã.

**Chữ trên trang:** cài đặt nhóm `report` (`report_*`, `otp_*`); trang admin để sửa làm ở H4b ("Chữ trên báo cáo & email").

**Máy chạy thử:** `APP_ENV=local` và `APP_DEBUG=true` thì trang nhập mã hiện luôn mã. Email gửi qua SMTP trong `.env` (máy Vân: hộp thư thử ở cổng 1025).

**Bảo mật:** không ghi họ tên, email, SĐT, mã vào log. Gửi email lỗi thì chỉ ghi tên loại lỗi.

**Dữ liệu ban đầu:** `FormFieldSeeder`, `SettingSeeder` (nhóm `report`). **Test:** `tests/Feature/Respondent/ReportFlowTest.php` (14 ca).
