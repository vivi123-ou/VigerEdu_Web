# CLAUDE.md – VigerEdu_CRM (CRM nội bộ, `crm.viger.edu.vn`)

> Claude Code tự đọc file này mỗi phiên.
>
> **Đặc tả hiện hành (ưu tiên cao nhất):**
> - `docs/THIET_KE_TONG_THE_V1.md` (chốt 14/11/2026);
> - `docs/HOP_DONG_KET_NOI.md` bản 3.
>
> **Giao diện:** làm theo `docs/mau_giao_dien_v3/index.html` (các màn hình CRM có mã `c-...`).
>
> **Tài liệu cũ, chỉ tham khảo phần dữ liệu và danh mục:** `docs/PROMPT_1_CRM.md`.
>
> **Tiến độ:** `docs/TIEN_DO.md`. Đọc đầu phiên, cập nhật cuối phiên.

## 1. Bối cảnh

- Tôi là **Vân (Vivi)**, Team Công nghệ, Viger-Edu (tư vấn du học Đức). Giao tiếp bằng **tiếng Việt**.
- Hệ thống gồm 4 ứng dụng độc lập, nối nhau **chỉ qua API**:
  - web chính `viger.edu.vn`;
  - Holland `holland.viger.edu.vn`;
  - **CRM này**;
  - chatbot Python.
- **CRM là hệ thống nội bộ riêng tư.**
  - Không có trang công khai. Toàn site cần đăng nhập và đặt `noindex`.
  - CRM là nơi duy nhất giữ hồ sơ khách.
- **Đa kênh.** Khách vào từ Holland, web chính, chatbot, Facebook Lead Ads, Zalo OA, sự kiện (nhập Excel) và nhập tay.
  - Mỗi kênh là một dòng trong bảng `channels`, có khóa API riêng, bật/tắt riêng.
  - Thêm kênh không cần sửa code.
- **Vai trò: Quản trị viên > Tư vấn viên > Nhân viên.** Vai trò và quyền lưu trong bảng; quản trị viên cấp tài khoản và đổi quyền trên giao diện.
  - **Quản trị viên:** xem toàn bộ, giao khách, gộp trùng, cấu hình, **là người duy nhất xuất dữ liệu**.
  - **Tư vấn viên:** chỉ thấy khách được giao; xem Holland đầy đủ; ghi chú, lịch hẹn, chuyển giai đoạn.
  - **Nhân viên:** chỉ thấy danh sách cần gọi 7 ngày gần nhất; gọi, ghi kết quả, đặt lịch.
- Mọi trường thông tin, danh sách lựa chọn, giai đoạn, tiêu chí xếp loại, kênh, vai trò, mẫu xuất đều **sửa được trên admin**.

## 2. Quy tắc

- **Laravel 13, PHP ≥ 8.3**, MySQL (production) / SQLite (dev, test), Blade + CSS/JS thuần (không SPA, không Tailwind).
- Package ngoài Laravel: chỉ **`openspout/openspout`**. Thêm package khác thì **hỏi tôi trước**.
- Nhánh `dev`, commit theo giai đoạn, message tiếng Việt. **Không push, không merge.** Không commit `.env`.
- Giao diện tiếng Việt, code tiếng Anh.
- **Chia module** theo `docs/THIET_KE_TONG_THE_V1.md` mục 3:
  - `app/Modules/<Tên>/` với `Services`, `Models`, `README.md`;
  - route riêng `routes/modules/<tên>.php`;
  - bật/tắt module trong `config/modules.php`;
  - controller mỏng, logic ở Service.
- **Chú thích bắt buộc:** mỗi class có PHPDoc tiếng Việt *Làm gì / Ai gọi / Dữ liệu*. Thuật toán (gộp trùng, xếp loại, giao tự động) có chú thích từng bước.
- Tên trường, mã, lựa chọn đúng `docs/TU_DIEN_DU_LIEU_CHUNG.xlsx` và `docs/lua_chon_chuan.json`. Trường tùy biến dùng bảng definitions + values, **không tạo cột bằng migration**, không dùng enum cứng.
- **Bảo mật:**
  - Khóa đăng nhập 15 phút sau 5 lần sai.
  - Mật khẩu tạm phải đổi ở lần đăng nhập đầu.
  - Tùy chọn giới hạn IP văn phòng.
  - SĐT, email, họ tên dùng cast `encrypted` cộng cột hash để tìm. **Không ghi vào log.**
  - Token API lưu dạng hash, có scope, mỗi kênh một token.
  - Phân quyền bằng Policy và scope truy vấn (`visibleTo($user)`), không lọc ở view. Mục không có quyền thì ẩn khỏi menu; gõ thẳng URL trả 403.
  - Chống sao chép danh sách:
    - chỉ quản trị viên có trang Xuất dữ liệu; mỗi lần xuất phải ghi lý do, ghi `export_logs`, link tải hết hạn sau 24 giờ;
    - danh sách 20 dòng / trang; xem quá 60 hồ sơ / giờ thì tạm khóa và báo quản trị viên;
    - mọi lần mở hồ sơ ghi `audit_logs`.
  - Migration chỉ thêm. Chặn `migrate:fresh/reset/db:wipe` khi production. Sao lưu hằng đêm.
- Dễ dùng: theo `docs/TIEU_CHI_DE_DUNG_VA_NGHIEM_THU.md`. Mỗi trang có dải "?" giải thích.
- Chưa rõ hoặc thấy mâu thuẫn: **hỏi tôi**, đừng đoán.

## 3. Nghiệp vụ cốt lõi

- **Intake** (`POST /api/v1/leads`, gói `lead/2`; `POST /api/v1/events`; `POST /api/v1/chat-sessions`):
  1. Kiểm tra token và scope của kênh.
  2. Kiểm tra `event_id`: đã nhận thì trả lại kết quả cũ (idempotent).
  3. Chuẩn hóa SĐT về `+84`.
  4. Khớp khách theo `customer_code` → SĐT → email.
     - Trùng SĐT mà khác họ tên và năm sinh: tạo mới và ghi `possible_duplicates`, **không tự gộp**.
     - Không ghi đè dữ liệu cũ bằng giá trị rỗng.
  5. Ghi `contact_events` (hành trình).
  6. Tính ưu tiên, giao người phụ trách theo quy tắc của kênh.
  7. Trả `{customer_code, lead_code, is_new, priority, assigned_to}`.
- **Xếp loại GREEN / YELLOW / RED:** đọc bảng `scoring_criteria`, không ghi cứng. Gồm 6 tiêu chí: học lực, ngoại ngữ, ngân sách, thời điểm, người quyết định, hành động đã làm.
- **Kết quả Holland:** nhận theo `holland-result/3` (điểm trung bình, mã 2/3 chữ, chỉ số C). **Bỏ hoàn toàn FIT / phần trăm của bản cũ.** Đề xuất trường, chương trình do tư vấn viên chọn trên CRM (gợi ý từ danh mục, không tự quyết).

## 4. Dữ liệu seed

`database/seeders/data/crm/` chứa dữ liệu dưới đây. Seeder idempotent theo `code`.

| File | Số mục | Ghi chú |
|---|---|---|
| `countries.json` | 3 | |
| `regions.json` | 17 | 16 bang, dữ liệu thật |
| `schools.json` | 390 | Trường HRK, dữ liệu thật đã chốt |
| `programs.json` | 30 | VÍ DỤ, cần kiểm chứng |
| `majors.json` | 75 | Bản cũ. Đối chiếu với danh mục 46 ngành của Holland trước khi dùng; hỏi tôi |
| `lead_scoring.json` | 6 tiêu chí | |

## 5. Cách làm mỗi phiên

1. Đọc `docs/TIEN_DO.md` và `git log --oneline -15`.
2. Báo kế hoạch của **một** giai đoạn, chờ tôi duyệt.
3. Làm. Không sửa file ngoài phạm vi giai đoạn.
4. Chạy `php artisan test` và `vendor/bin/pint --dirty`.
5. Commit, cập nhật `docs/TIEN_DO.md`, báo kết quả rồi dừng.

## 6. Giai đoạn (chi tiết: `docs/THIET_KE_TONG_THE_V1.md` mục 10)

- [ ] C1. Khởi tạo Laravel 13; hệ thống thiết kế (màu, logo VIGER EDU, Inter); đăng nhập; bảng `roles/permissions`; cấp tài khoản
- [ ] C2. Bảng dữ liệu (THIET_KE mục 4.2); seeder danh mục; trường tùy biến; giai đoạn; tiêu chí xếp loại
- [ ] C3. Module Intake + Channels: `lead/2`, `events/1`, `chat-session/2`, khóa kênh, gộp trùng, idempotent, giao tự động
- [ ] C4. Khách hàng, hồ sơ 360 + hành trình, cần gọi, lịch hẹn; test phạm vi dữ liệu từng vai trò
- [ ] C5. Tổng quan đa kênh, báo cáo, xuất dữ liệu (chỉ quản trị viên) + nhật ký
- [ ] C6. Kênh Facebook, Zalo (webhook), nhập Excel sự kiện
- [ ] C7. Bảo mật: giới hạn xem hồ sơ, nhật ký truy cập, sao lưu
- [ ] C8. Cài đặt, `/install`, README deploy, sổ tay, nghiệm thu
