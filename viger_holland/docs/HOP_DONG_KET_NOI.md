# HỢP ĐỒNG KẾT NỐI GIỮA CÁC ỨNG DỤNG – VIGER EDU (bản 3, 14/11/2026)

> Thay bản 2. Dùng chung cho Web chính (Tuấn), Holland và CRM (Vân), Chatbot (Ý).
> Bản 2 (`lead/1`, `holland-result/1` kiểu FIT) **không dùng nữa**.
> Thiết kế tổng thể: `docs/THIET_KE_TONG_THE_V1.md`.

## 1. Quy ước chung

- HTTPS, JSON UTF-8, đường dẫn `/api/v1/...`.
- Xác thực: header `X-Api-Token: <token>`.
  - CRM cấp **một token cho mỗi kênh** (holland, website, chatbot, facebook, zalo…), quản trị viên tạo/thu hồi ở trang "Kênh và nguồn".
  - Token chỉ nằm trong `.env` phía server, **không bao giờ** ở JavaScript trình duyệt.
  - CRM lưu token dạng hash, so sánh `hash_equals`.
- Mỗi token có **scope**:
  - `leads:write`, `events:write`, `chat:write`: gửi dữ liệu vào;
  - `catalog:read`: đọc danh mục.

  Holland cấp token `results:read` cho chatbot và CRM.
- Mã lỗi:

  | Mã | Ý nghĩa |
  |---|---|
  | `401` | Sai hoặc thiếu token |
  | `403` | Token không có quyền (scope) |
  | `422` | Dữ liệu sai, kèm `errors` chỉ ghi **tên trường**, không lặp lại giá trị |
  | `429` | Quá 60 lần mỗi phút |
  | `5xx` | Lỗi phía nhận; bên gửi thử lại sau |

- Mỗi gói có `schema_version` và `event_id` (UUID). Gửi lại cùng `event_id` thì CRM trả lại kết quả cũ, không tạo bản ghi mới.
- **Không gửi dữ liệu cá nhân trong URL.** Không ghi SĐT, email, họ tên vào log ở cả hai phía.

## 2. API của CRM (`https://crm.viger.edu.vn/api/v1`)

### 2.1 `POST /leads` – nhận khách từ mọi kênh (`lead/2`)

```json
{
  "schema_version": "lead/2",
  "event_id": "5c0e8a3e-2f7b-4f3b-9a51-1d2a7c1e9b10",
  "channel": "holland",
  "external_ref": "HL-9f2c",
  "customer_code": null,
  "consent": { "agreed": true, "version": "dieu-khoan-2026-11", "agreed_at": "2026-11-14T09:35:00+07:00",
               "guardian_required": true, "guardian_confirmed": true },
  "contact": {
    "role": "HS", "full_name": "Nguyễn Văn A", "birth_year": 2009,
    "phone": "+84900000000", "phone_verified": false,
    "email": "a@example.com", "email_verified": true,
    "province": "TP.HCM"
  },
  "guardian": { "full_name": null, "phone": null, "email": null },
  "profile": { "current_level": "Lớp 11", "current_school": null, "gpa": 8.2,
               "english_level": "IELTS 6.0+", "german_level": null, "budget": null, "timing": null },
  "extra_fields": { "truong_chuyen": true },
  "holland_result": { "...": "xem mục 3.2 (holland-result/3)" },
  "quiz": {
    "attempt_id": "9f2c…", "completed_at": "2026-11-14T09:33:00+07:00", "form_version": "cfg-12",
    "answers": [ { "code": "I012", "group": "I", "value": 4, "ms": 5200 } ],
    "view_url": "https://holland.viger.edu.vn/admin/attempts/9f2c…"
  },
  "events": [ { "type": "quiz_finished", "at": "2026-11-14T09:33:00+07:00", "meta": { "duration_s": 460 } } ],
  "utm": { "source": "facebook", "medium": "post", "campaign": "tam-ly-2026" },
  "message": null
}
```

Quy tắc:

- **Bắt buộc:**
  - `consent.agreed = true`;
  - có ít nhất `contact.phone` hoặc `contact.email`.
- SĐT chuẩn hóa về `+84…` ở **bên gửi**; CRM chuẩn hóa lại lần nữa.
- **Chỉ gửi trường có giá trị.** Không gửi chuỗi rỗng hay `null` cho trường không có dữ liệu.
- Trường tùy biến do admin Holland thêm đi vào `extra_fields`. CRM tự tạo định nghĩa trường nếu chưa có, ở trạng thái "Chờ duyệt".
- **CRM khớp khách theo thứ tự:** `customer_code` → SĐT → email.
  - Trùng SĐT nhưng khác họ tên và năm sinh: tạo hồ sơ mới, đánh dấu "có thể trùng".
  - Không ghi đè giá trị cũ bằng giá trị rỗng.

Trả về:

```json
{ "customer_code": "KH0001", "lead_code": "LD0042", "is_new": true, "priority": "GREEN", "assigned_to": "Võ Tư Vấn" }
```

Holland lưu `customer_code` và gửi kèm ở các lần sau.

### 2.2 `POST /events` – hành vi bổ sung (`events/1`)

Dùng khi khách quay lại xem báo cáo, bấm Gọi/Zalo…, sau khi đã có `customer_code`.

```json
{ "schema_version": "events/1", "event_id": "…", "channel": "holland", "customer_code": "KH0001",
  "events": [ { "type": "report_viewed", "at": "2026-11-14T09:50:00+07:00", "meta": { "device": "mobile" } } ] }
```

Loại sự kiện chuẩn:

| Nhóm | Loại sự kiện |
|---|---|
| Trang và bài làm | `page_viewed`, `quiz_started`, `quiz_page_completed`, `quiz_finished` |
| Báo cáo | `report_unlocked`, `report_viewed` |
| Nút liên hệ | `cta_call_clicked`, `cta_zalo_clicked` |
| Form và chatbot | `form_submitted`, `chat_handoff` |

Loại mới thì thêm vào bảng loại sự kiện trên CRM; các loại cũ không đổi.

### 2.3 `POST /chat-sessions` – phiên chat (`chat-session/2`)

Giữ cấu trúc bản 2. Khác biệt:

- thêm `event_id`;
- thêm `customer_code`, hoặc `contact.phone`/`contact.email` để gắn vào khách.

Gửi nhiều lần cùng `session_code` thì CRM nối thêm tin nhắn mới.

### 2.4 `GET /catalog/{majors|schools|programs|regions}` – danh mục (scope `catalog:read`)

### 2.5 `GET /ping` – kiểm tra kết nối

Trả `{ "ok": true, "channel": "holland" }`.

## 3. API của Holland (`https://holland.viger.edu.vn/api/v1`)

### 3.1 `GET /results/{token}?view=khach|tuvan` (scope `results:read`)

Báo cáo của một lượt làm bài:

- `view=tuvan` có đủ mã 3 chữ, mã thay thế, độ tin cậy, 36 câu;
- `view=khach` giống bản khách xem.

### 3.2 Cấu trúc `holland-result/3`

```json
{ "schema_version": "holland-result/3", "engine_version": "average_v3",
  "scores": { "R": 3.33, "I": 4.67, "A": 4.17, "S": 4.33, "E": 2.33, "C": 3.17 },
  "item_counts": { "R": 6, "I": 6, "A": 6, "S": 6, "E": 6, "C": 6 },
  "ranking": ["I","S","A","R","C","E"],
  "code_full": "ISA", "code_public": "IS", "alternates": ["SIA","IAS"],
  "relation": "cach_mot", "consistency": "Trung bình", "reliability": "Tốt", "flags": [],
  "majors": [ { "id": 12, "name_vi": "Tâm lý học", "name_de": "Psychologie", "c_index": 15, "label": "Liên quan nhiều" } ] }
```

Không có phần trăm phù hợp.

### 3.3 `GET /catalog/{types|pairs|third|majors}`

### 3.4 `GET /public/intro`

Khối giới thiệu cho web chính, **không cần token**.

## 4. Gắn vào web chính

- Nút / menu "Làm trắc nghiệm" trỏ tới `https://holland.viger.edu.vn/?utm_source=viger.edu.vn`.
- Form liên hệ:
  - server web chính gọi `POST crm/api/v1/leads` với `channel: "website"`, token kênh website;
  - CRM lỗi thì web chính vẫn lưu form như hiện tại và gửi lại sau.
- Chatbot: dán 1 dòng `<script>` vào layout.

## 5. Gắn / gỡ

| Việc | Làm ở đâu | Ảnh hưởng |
|---|---|---|
| Thêm kênh mới (ví dụ TikTok) | CRM → Kênh và nguồn → Thêm kênh → cấp token | Không sửa code CRM |
| Tắt một kênh | CRM → Kênh và nguồn | Kênh đó nhận `403`; dữ liệu cũ giữ nguyên |
| Gỡ CRM | Holland → Kết nối CRM → tắt | Holland vẫn chạy, hồ sơ chờ trong hàng đợi |
| Thay chatbot | Thu hồi token cũ, cấp token mới | Không ảnh hưởng ứng dụng khác |
