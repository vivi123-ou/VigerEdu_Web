# PROMPT 4 – Thu thập thông tin khách và gửi sang CRM (repo VigerEdu_Holland, nhánh feature/holland-v3)

> Dán toàn bộ nội dung dưới đây vào Claude Code trong VS Code, mở đúng thư mục repo Holland.
> Chạy **sau khi Giai đoạn 1 và 2 đã xong** (đã có bảng dữ liệu, 300 câu, bộ máy chấm `holland-result/3`).
> Dùng cho giai đoạn **H4 + H5** trong `CLAUDE.md`. Gói gửi đúng `docs/HOP_DONG_KET_NOI.md` bản 3 (`lead/2`, `events/1`). Mọi quyết định đã chốt ở `docs/THIET_KE_TONG_THE_V1.md` mục 0 (làm bài trước, OTP email, 6 câu/trang, vai trò).

---

Đọc `CLAUDE.md`, `docs/TIEN_DO.md`, `docs/HOP_DONG_KET_NOI.md`, `docs/PROMPT_3_CAP_NHAT_HOLLAND.md` (mục 3, 6), `database/seeders/data/holland_v3/form_fields.json`, `docs/TU_DIEN_DU_LIEU_CHUNG.xlsx`, `docs/lua_chon_chuan.json` và bản mẫu `docs/mau_giao_dien/2-ket-qua-so-luoc.html`, `3-bao-cao-khach.html`.

Việc của giai đoạn này: **luồng khách hoàn chỉnh, thu thập thông tin khách và gửi sang CRM**. Repo CRM (`VigerEdu_CRM`) hiện còn trống, nên phía Holland phải chạy độc lập: gửi theo hợp đồng API, có hàng đợi, CRM chưa có thì khách vẫn dùng bình thường. Test bằng `Http::fake()`.

## 0. Quyết định đã chốt (ghi đè `CLAUDE.md` mục 2 và bảng quyết định trong `docs/TIEN_DO.md`)

1. **Thứ tự luồng khách (theo bản mẫu 2):** giới thiệu → làm bài 36 câu → **kết quả sơ lược** (mã 2 chữ, 1 câu mô tả, tên 2–3 ngành, phần còn lại làm mờ) → **form thông tin** → **mã OTP** → **báo cáo đầy đủ**. Không bắt điền thông tin trước khi làm bài.
2. **Form chỉ bắt buộc 4 trường:** họ tên, số điện thoại, năm sinh, ô đồng ý (không tích sẵn). Email nhận báo cáo: bắt buộc nếu dùng OTP qua email (mục 3). Các trường còn lại nằm trong khối "Thông tin thêm (không bắt buộc)" thu gọn.
3. **Kênh OTP V1: email** (miễn phí, đúng bản mẫu 2). Viết theo interface `OtpChannel` để sau này thêm SMS/Zalo bằng cài đặt, không sửa luồng. Số điện thoại vẫn bắt buộc nhưng ghi `phone_verified = false`.
4. **CRM tạm lỗi hoặc chưa bật kết nối thì khách vẫn xem được báo cáo.** Gửi CRM chạy nền qua hàng đợi.
5. Không có nút đặt lịch. Tư vấn viên liên hệ từ CRM.

Sửa `CLAUDE.md` mục 2 và mục 7, thêm dòng quyết định vào `docs/TIEN_DO.md` cho khớp 5 điểm trên.

## 1. Ánh xạ trường thông tin sang hợp đồng CRM

`form_fields` đang dùng khóa tiếng Việt (`ho_ten`, `so_dien_thoai`…), hợp đồng `POST /leads` dùng khóa tiếng Anh (`student_name`, `phone`…). Không ghi cứng ánh xạ trong code:

- Migration **chỉ thêm** cột `crm_key` (nullable) vào `form_fields`.
- Seeder điền: `ho_ten → student_name`, `so_dien_thoai → phone`, `nam_sinh → birth_year`, `email → email`, `dong_y → consent`, `sdt_phu_huynh → parent_phone`, `tinh → province`, `truong_dang_hoc → current_school`, `lop → current_level`. Các trường khác đối chiếu `docs/TU_DIEN_DU_LIEU_CHUNG.xlsx`. Trường nào từ điển không có thì để `crm_key = null`.
- Khi đóng gói: trường có `crm_key` → đặt ở gốc gói; trường `crm_key = null` → `extra_fields.{key}`.
- **Chỉ gửi trường `to_crm = true` và có giá trị.** Không gửi chuỗi rỗng, không gửi `null`.
- Trang admin "Trường thông tin" thêm cột "Tên trường bên CRM" (ô chọn từ danh sách khóa trong từ điển, hoặc "Trường mở rộng"), có nút "?" giải thích.

Nếu khóa nào trong từ điển không rõ nghĩa: **hỏi tôi**, đừng đoán.

## 2. Dữ liệu (migration chỉ thêm)

| Bảng | Cột chính | Ghi chú |
|---|---|---|
| `respondents` | id, full_name (encrypted), phone (encrypted), phone_hash, email (encrypted), email_hash, birth_year, fields (json, encrypted), crm_customer_code, created_at | Một người làm bài. `phone_hash`, `email_hash` = HMAC-SHA256 với khóa trong `.env`, dùng để tìm người cũ |
| `consents` | id, respondent_id, attempt_id, version, agreed_at, guardian_required, ip_hash | Năm sinh cho thấy dưới 18 tuổi thì `guardian_required = true`, form hiện thêm ô "Phụ huynh đã đồng ý" (văn bản lấy từ cài đặt) |
| `otp_codes` | id, attempt_id, channel, target_hash, code_hash, expires_at, attempts, sent_count | Mã 6 số, hiệu lực 5 phút, sai 5 lần thì khóa 30 phút, tối đa 3 lần gửi / giờ / địa chỉ. Các con số đọc từ cài đặt |
| `crm_outbox` | id, event_id (uuid, unique), attempt_id, payload (encrypted json), status (pending / sent / failed / disabled), tries, next_try_at, last_http_status, last_error, sent_at | `last_error` **không chứa** dữ liệu cá nhân |

`attempts` thêm `respondent_id` (nullable), `unlocked_at`.

Số điện thoại chuẩn hóa về `+84…` bằng `app/Support/PhoneNumber.php` trước khi băm và lưu.

## 3. Luồng và màn hình

1. **Kết quả sơ lược** (`/ket-qua/{result_token}`): theo bản mẫu 2. Chữ, số ngành hiện, câu mời đọc từ cài đặt.
2. **Form thông tin:** dựng từ `form_fields` (`FormSchema`); FormRequest kiểm tra; throttle 5/phút; Cloudflare Turnstile nếu cài đặt bật (khóa trong `.env`); câu đồng ý và phiên bản điều khoản lấy từ cài đặt.
3. **OTP:** gửi mã, màn hình nhập 6 ô như bản mẫu 2, có nút "Gửi lại" (đếm ngược). Đúng mã thì:
   - tìm `respondents` theo `phone_hash` (sau đó `email_hash`); có thì gắn lượt làm vào người cũ, **không ghi đè giá trị cũ bằng ô trống**; chưa có thì tạo mới;
   - lưu `consents`, đặt `attempts.unlocked_at`;
   - gọi `CrmSync::queue($attempt)`;
   - chuyển tới báo cáo đầy đủ (bản mẫu 3).
4. **Email báo cáo:** gửi link báo cáo (không gửi điểm chi tiết trong thân email). Mailer lấy từ `.env`.
5. Học sinh quay lại cùng số điện thoại / email: hiện các lần làm trước, cho xem lại báo cáo hoặc làm lần mới.

## 4. Gửi sang CRM

- `app/Services/Holland/CrmSync.php`:
  - `queue(Attempt)`: đóng gói rồi ghi `crm_outbox` (status `pending`, hoặc `disabled` nếu kết nối CRM đang tắt);
  - `buildPayload(Attempt): array`: dựng gói, tách riêng để test.
- **Gói gửi:** giữ cấu trúc `POST /api/v1/leads` trong `docs/HOP_DONG_KET_NOI.md`, nâng `schema_version` thành `lead/2`:
  - `source: "holland"`, `external_ref` = mã lượt làm, `event_id` = uuid của outbox;
  - các trường theo mục 1; `consent: true` kèm `consent_version`, `consent_at`; `phone_verified`, `email_verified`;
  - `holland_result` theo `holland-result/3`: 6 điểm trung bình, `code_full`, `code_public`, mã thay thế, ngành gợi ý (mã, tên, nhãn), độ tin cậy, `engine_version`;
  - `quiz.answers[]`: `code`, `group`, `value` (1–5), `ms`; kèm `quiz.view_url` = link báo cáo nội bộ (cần đăng nhập).
- **Job `SendLeadToCrm`** (queue database):
  - `Http::withHeaders(['X-Api-Token' => …])->timeout(10)->post(crm_url.'/api/v1/leads', $payload)`;
  - 2xx: `status = sent`, lưu `customer_code` trả về vào `respondents.crm_customer_code`. Các lần gửi sau đưa kèm mã này;
  - 422: `failed`, ghi `last_error` (chỉ tên trường lỗi, không có giá trị);
  - 401 / 429 / 5xx / mất mạng: thử lại theo lịch 1, 5, 30 phút, 2 giờ, 6 giờ (đọc từ cài đặt); hết lượt thì `failed`.
- Lệnh `php artisan crm:retry` chạy theo lịch mỗi 5 phút, gửi lại các bản ghi tới hạn. Lệnh `php artisan crm:payload {attempt}` in gói mẫu **đã che** SĐT, email, họ tên, để người làm CRM biết dữ liệu sẽ nhận.
- **Không ghi SĐT, email, họ tên vào log.** Log chỉ có `event_id`, mã lượt làm, mã HTTP.

## 4b. Ghi hành vi (module Tracking)

- Bảng `tracking_events` (test_session_id, type, page, meta json, occurred_at). Ghi các loại ở hợp đồng mục 2.2 bằng một route `POST /t` nhẹ (throttle, không cần đăng nhập, chỉ nhận loại có trong danh sách).
- Trước khi khách đồng ý: sự kiện chỉ gắn với lượt làm bài, không gắn người. Khi đóng gói `lead/2`, gửi kèm `events[]` của lượt làm bài. Sau khi đã có `customer_code`, sự kiện mới gửi bằng `POST /events` qua cùng hàng đợi.

## 5. Trang admin "Kết nối CRM" (Cài đặt → Kết nối)

- Bật / tắt "Gửi khách sang CRM", địa chỉ CRM, token. Token lưu mã hóa, ô nhập chỉ hiện 4 ký tự cuối. Ưu tiên `.env` nếu có.
- Nút **"Kiểm tra kết nối"**: gọi `GET {crm_url}/api/v1/ping` với token, báo kết quả bằng lời thường.
- **Bảng hàng đợi:** thời gian, mã lượt làm, SĐT đã che (`09xx xxx 678`), trạng thái, số lần thử, lỗi gần nhất; lọc theo trạng thái; nút "Gửi lại" từng dòng và "Gửi lại tất cả bản lỗi". Bật lại kết nối thì các bản `disabled` chuyển sang `pending`.
- Có chú thích "Trang này làm gì?" và nút "?" như các trang khác. Chỉ quản trị viên vào được.

## 6. Cập nhật hợp đồng cho phía CRM

Sửa `docs/HOP_DONG_KET_NOI.md` mục 3.1:

- ghi gói `lead/2` đầy đủ (mẫu JSON với dữ liệu giả), bảng ánh xạ ở mục 1, `holland-result/3`;
- quy tắc phía CRM: trùng `event_id` thì trả lại kết quả cũ; tìm khách theo `customer_code` → SĐT → email; trùng SĐT mà khác họ tên và năm sinh thì tạo hồ sơ mới và đánh dấu "có thể trùng", không tự gộp;
- thêm `GET /api/v1/ping`.

Giữ phần `lead/1` làm lịch sử, ghi rõ đã thay.

## 7. Test (PHPUnit, SQLite, `Http::fake()`)

1. Gói chỉ có trường `to_crm` có giá trị. Trường admin mới thêm ("Trường chuyên?") đi vào `extra_fields` khi được điền, không có khi bỏ trống.
2. Ánh xạ `crm_key` đúng; đổi `crm_key` trên admin thì gói đổi theo, không sửa code.
3. OTP: sai 5 lần bị khóa; hết hạn bị từ chối; đúng mã thì mở báo cáo và tạo đúng 1 dòng outbox.
4. CRM trả 500: khách vẫn xem được báo cáo; outbox `pending`, `tries = 1`, có `next_try_at`. Lần sau trả 200: `sent`, lưu `customer_code`.
5. CRM trả 422: `failed`; `last_error` không chứa SĐT / email.
6. Gửi lại cùng một outbox: cùng `event_id`.
7. Kết nối tắt: outbox `disabled`, không có request; bật lại thì gửi.
8. Người cũ làm lại: gắn vào cùng `respondent`; ô bỏ trống lần sau không xóa giá trị cũ.
9. Dưới 18 tuổi: bắt buộc ô phụ huynh đồng ý.
10. Quét `storage/logs` sau toàn bộ test: không có chuỗi SĐT / email / họ tên giả đã dùng.
11. Trang kết quả, báo cáo, admin đều có `noindex, nofollow`.

## 8. Cách làm

- Làm trên `feature/holland-v3`. **Không push, không merge**, không commit `.env`.
- Trước khi viết code, **báo kế hoạch** (danh sách file, migration, route, màn hình) và chờ tôi duyệt.
- Chia nhỏ commit:
  - 4a: dữ liệu + ánh xạ;
  - 4b: form + OTP + mở báo cáo;
  - 4c: CrmSync + outbox + job;
  - 4d: admin Kết nối CRM;
  - 4e: hợp đồng + tài liệu.
- Sau mỗi phần: `php artisan test` + `vendor/bin/pint --dirty`, commit message tiếng Việt, cập nhật `docs/TIEN_DO.md`, rồi dừng báo kết quả.
- Cuối giai đoạn: liệt kê các bước để tôi tự thử trên máy:
  - làm bài trên điện thoại 390px;
  - nhận OTP qua Mailpit;
  - xem hàng đợi khi chưa có CRM;
  - bật một CRM giả bằng lệnh `crm:payload`.
- Gặp chỗ mâu thuẫn giữa prompt này, `CLAUDE.md` và `PROMPT_3`: **hỏi tôi**, không tự chọn.
