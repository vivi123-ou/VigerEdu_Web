# Module Respondent (thông tin khách)

**Làm gì:** quản lý các ô của form "Cập nhật thông tin để xem báo cáo" và lưu câu trả lời của khách. Từ H4 sẽ có thêm form, mã xác nhận qua email, câu đồng ý, báo cáo đầy đủ.

**Bảng:**
- `form_fields`: các ô của form. `key` là mã nội bộ (ví dụ `ho_ten`), `crm_key` là tên trường gửi sang CRM, theo `docs/TU_DIEN_DU_LIEU_CHUNG.xlsx` (ví dụ `student_name`). Admin đổi chữ, thứ tự, bắt buộc / không bắt buộc, ẩn hiện;
- `field_values`: giá trị khách điền, gắn với lượt làm bài.

**7 ô mặc định (chốt 14/11, theo mẫu UEH):**
- bắt buộc: Họ tên, Năm sinh của học sinh, Email, Điện thoại / Zalo, Thuộc đối tượng (`HS` / `PH` / `KHAC`);
- không bắt buộc: Trường, Tỉnh / thành phố.

Học lực, ngoại ngữ, điểm môn, ngân sách do CRM thu sau, không hỏi ở đây.

**Bảo mật:** không ghi họ tên, số điện thoại, email vào log.

**Dữ liệu ban đầu:** `FormFieldSeeder`. **Route / test:** làm ở H4 và H6.
