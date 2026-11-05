# HỢP ĐỒNG KẾT NỐI GIỮA CÁC WEB – Viger-Edu (bản 2)

Tài liệu chung cho cả nhóm: **Tuấn** (web chính), **Vân** (Holland + CRM), **Ý** (AI chatbot), và người phụ trách mạng xã hội.
Mục đích: mỗi web là **một công cụ riêng**, gỡ ra hoặc thay thế được. Các web chỉ nói chuyện với nhau qua **các API dưới đây**, không đọc database của nhau.

## 1. Sơ đồ

```
                        viger.edu.vn  (PLATFORM 1 – web chính, Tuấn; có trang quản trị nội dung riêng)
                        │ mục "Test Holland" (link/nhúng) · nút Gọi / Zalo · ô "Mã nhúng chatbot" trong admin
                        │ form liên hệ ──(tùy chọn)──► CRM
        ┌───────────────┼──────────────────────────────┐
        ▼               ▼                              ▼
holland.viger.edu.vn   AI chat (Python – Ý, ĐỘC LẬP)        Mạng xã hội (Facebook, Zalo…)
(PLATFORM 2 – Vân)     chat.viger.edu.vn                │
   │  POST lead           │ POST lead, chat-session      │ (sau này: webhook / nhập tay)
   │  GET danh mục ngành  │ GET câu hỏi, POST chấm điểm   │
   ▼                      ▼                              ▼
                 crm.viger.edu.vn  (PLATFORM 3 – Vân; RIÊNG TƯ, chỉ nhân viên đăng nhập)
                 tìm khách theo SĐT → xem bài test + tin nhắn chatbot · xếp loại · lịch hẹn · đề xuất · xuất Excel tự động
```

Ai sở hữu dữ liệu gì:

| Web | Sở hữu | Cung cấp cho web khác |
|---|---|---|
| **CRM** | Khách hàng, lịch sử liên hệ, xếp loại, lịch hẹn, đề xuất trường, **danh mục** (quốc gia, khu vực, trường, chương trình, ngành + mã Holland) | API nhận khách, nhận phiên chat, đọc danh mục ngành |
| **Holland** | Bộ câu hỏi, lượt làm bài, kết quả chấm, nội dung trang test | API lấy câu hỏi, chấm điểm, **khối giới thiệu cho web chính**; gửi khách **kèm bài làm chi tiết** sang CRM |
| **Chatbot** | Hội thoại, kho kiến thức AI | (không cung cấp API) gửi khách, phiên chat **kèm toàn bộ tin nhắn** sang CRM |
| **Web chính** | Bài viết, trang giới thiệu | Link sang Holland, gắn khung chat, (tùy chọn) gửi form liên hệ sang CRM |

**Thay thế một web:** web mới chỉ cần gọi đúng các API này (hoặc cung cấp đúng các API này) là các web khác không phải sửa.

## 2. Quy ước chung

- HTTPS, JSON UTF-8, đường dẫn có phiên bản `/api/v1/...`.
- Xác thực: header `X-Api-Token: <token>`. **Mỗi web gọi có token riêng** (CRM cấp token cho Holland, Chatbot, Web chính; Holland cấp token cho Chatbot). Token chỉ để ở server (`.env`), **không bao giờ để trong JavaScript của trình duyệt**.
- Mã lỗi: `401` sai/thiếu token · `422` dữ liệu sai (kèm `errors`) · `429` gọi quá nhiều (60 lần/phút) · `5xx` lỗi server (bên gọi thử lại sau, không làm hỏng trải nghiệm của khách).
- Mỗi gói dữ liệu có `schema_version` để sau này đổi cấu trúc mà không làm hỏng bên cũ.

## 3. API của CRM (`https://crm.viger.edu.vn/api/v1`)

### 3.1 `POST /leads` – nhận khách từ bất kỳ nguồn nào
```json
{
  "schema_version": "lead/1",
  "source": "holland",                     // holland | chatbot | website_contact | event | facebook | zalo | ctv | manual (xem TU_DIEN tab 3)
  "external_ref": "HL-9f2c…",              // mã lượt test / mã phiên chat ở bên gửi
  "consent": true,                          // bắt buộc true (đồng ý lưu thông tin)
  "role": "PH",                             // HS | PH
  "student_name": "Nguyễn Văn A", "birth_year": 2009,
  "phone": "09xxxxxxxx", "email": "a@example.com",     // bắt buộc có ít nhất 1
  "parent_name": null, "parent_phone": null, "province": "TP.HCM",
  "current_level": "Lớp 11", "current_school": null, "gpa": 8.2,
  "english_level": "IELTS 6.0+", "german_level": "Đức A1-A2",
  "desired_level": "Đại học", "country": "Đức", "region": "Hessen",
  "interested_major": "Chưa biết", "budget": "300-500 triệu", "timing": "1-2 năm",
  "appointment_at": null,
  "extra_fields": { "truong_chuyen": true },               // trường tùy biến do admin tự thêm (mở rộng không cần đổi hợp đồng)
  "holland_result": { … HollandResult, xem mục 4 … },     // tùy chọn
  "quiz": {                                                // tùy chọn – bài làm chi tiết (Holland gửi)
    "attempt_id": "9f2c…", "completed_at": "2026-11-03T15:20:00+07:00",
    "question_set_version": "2026-11-03",
    "answers": [
      {"code":"B01","type":"yes_no","text":"Người thích làm việc bằng tay…","answer":"Đúng",
       "group":"R","value":1,"max":1,"scores":[{"group":"R","value":1,"max":1}]},
      {"code":"B60","type":"multi","text":"Bạn thích hoạt động nào?","answer":"Bán hàng, Sắp xếp sổ sách",
       "group":null,"value":3,"max":4,"scores":[{"group":"E","value":1,"max":2},{"group":"C","value":2,"max":2}]},
      …
    ],
    "view_url": "https://holland.viger.edu.vn/admin/attempts/9f2c…"     // link xem bài gốc (cần đăng nhập Holland)
  }
}
```
**`quiz.answers` (bổ sung 05/11/2026, chỉ thêm trường, không đổi trường cũ):**
- `type`: kiểu câu – `yes_no`, `likert3`, `likert5`, `scale` (thang mức tự đặt), `single` (chọn 1), `multi` (chọn nhiều).
- `answer`: chữ khách đã chọn (câu chọn nhiều: các lựa chọn cách nhau bằng dấu phẩy).
- `scores`: điểm theo từng nhóm `[{group, value, max}]`. Câu chọn 1 / chọn nhiều có thể cộng cho nhiều nhóm.
- `group` / `value` / `max` giữ như cũ: `group` là chữ nhóm khi câu chỉ cộng cho một nhóm, `null` khi cộng cho nhiều nhóm (xem `scores`); `value` / `max` là tổng.
- Câu không bắt buộc mà khách bỏ trống thì không có trong `answers`.
Trả về:
```json
{ "customer_code": "KH0001", "is_new": true, "lead_tier": "GREEN", "handoff_now": true }
```
CRM tự khớp khách cũ theo SĐT hoặc email, cập nhật thông tin, lưu lịch sử, tính lại xếp loại và đề xuất trường.

### 3.2 `POST /chat-sessions` – nhận phiên chat
```json
{ "schema_version":"chat-session/1", "session_code":"C0001", "customer_code":"KH0002",
  "channel":"web", "started_at":"2026-11-03T20:01:00+07:00", "ended_at":"2026-11-03T20:09:00+07:00",
  "message_count":18, "intent":"Hỏi ngành", "info_percent":80,
  "holland_code_preliminary":"RIC", "confidence":"Thấp",
  "handed_off":true, "handoff_reason":"Khách GREEN", "ai_summary":"SV năm 2, GPA 7.4, …",
  "transcript_url":null,
  "messages": [                                   // toàn bộ hội thoại (bắt buộc từ bản 2)
    {"at":"2026-11-03T20:01:05+07:00","role":"user","text":"Em muốn học kỹ thuật ở Đức"},
    {"at":"2026-11-03T20:01:09+07:00","role":"assistant","text":"Chào bạn, …"} ] }
```
Phiên chat cũng có thể kèm `extra_fields` (thông tin chatbot thu được ngoài các trường chuẩn).
Gửi nhiều lần cho cùng `session_code` (ví dụ mỗi 10 tin) thì CRM **nối thêm** tin nhắn mới, không tạo phiên trùng. Có thể gửi `phone`/`email` trong phiên để CRM tự gắn vào khách.

### 3.3 `GET /catalog/majors?type=university|vocational&active=1` – danh mục ngành
Trả danh sách ngành: `code, type, name_vi, name_de, name_en, field, testas_primary, testas_secondary, holland_codes[], description, subjects, careers, suitable_for, school_types, languages, keywords`.

## 4. API của Holland (`https://holland.viger.edu.vn/api/v1`)

### 4.1 `GET /questions?part=A|B` – bộ câu hỏi đang bật

### 4.2 `POST /score` – chấm điểm (không lưu, không gửi CRM)
Vào: `{ "answers": [ {"group":"R","value":1,"max":1}, … ] }` hoặc `{ "percent": {"R":0.33,"I":0.67,"A":0.89,"S":0.56,"E":0.11,"C":0.22} }`
Ra (**HollandResult**):
```json
{ "schema_version":"holland-result/1", "engine":"riasec_fit_v2", "engine_version":"2.0",
  "percent":{"R":0.33,"I":0.67,"A":0.89,"S":0.56,"E":0.11,"C":0.22},
  "code":"AIS", "di3":0.39, "consistency":2, "has_tie":false,
  "top_university":[{"major_code":"N043","name":"Ngôn ngữ và Văn học Đức","code_used":"IAS",
     "code_match":0.7222,"score_match":0.9193,"fit":0.8011,"label":"Rất phù hợp",
     "reason":"AIS ↔ IAS · bạn đạt I 67%, A 89%, S 56%"}],
  "top_vocational":[ … ],
  "fields":[{"field":"Ngôn ngữ – Văn hóa – Truyền thông","score":0.8011,"best_major_code":"N043"}],
  "testas":[{"group":"G4","score":0.8011}] }
```
Gọi `POST /score?full=1` thì có thêm `"ranking": [ … toàn bộ ngành đang bật, cùng các trường như top_university … ]` (CRM dùng cho nút "Xem xếp hạng đầy đủ").
Một thuật toán Holland khác chỉ cần trả đúng cấu trúc này là CRM và chatbot dùng được ngay.

### 4.3 `GET /public/intro` – khối giới thiệu cho web chính (KHÔNG cần token, chỉ đọc)
Trả nội dung mà admin Holland soạn: `{ "title", "subtitle", "image_url", "button_text", "start_url", "stats": {"total_attempts": 1234} }`. Web chính dùng để hiển thị mục "Test Holland" (tự cập nhật khi admin Holland đổi nội dung). Có thêm `GET /embed` (trang rút gọn để nhúng iframe) và `GET /embed.js` (tự tạo iframe co giãn theo chiều cao).

## 5. Gắn vào web chính (WordPress hoặc Laravel đều như nhau)

1. **Mục "Test Holland"**: (a) link/menu tới `https://holland.viger.edu.vn?utm_source=viger.edu.vn`; (b) khối giới thiệu lấy từ `GET /public/intro`; hoặc (c) nhúng `<script src="https://holland.viger.edu.vn/embed.js" data-target="#holland"></script>`.
2. **Khung chat AI** (độc lập): mỗi web có ô **"Mã nhúng chatbot"** trong trang quản trị; dán/xóa là gắn/gỡ. Ý cung cấp **1 đoạn `<script src="https://chat.viger.edu.vn/widget.js" defer></script>`**.
   - WordPress: dán vào footer bằng plugin chèn code (ví dụ WPCode / Insert Headers and Footers) hoặc file `footer.php` của theme con.
   - Laravel: dán vào `layouts/app.blade.php` trước `</body>`.
3. **Nút Gọi / Zalo**: link `tel:` và `https://zalo.me/<số>` – không cần kết nối dữ liệu.
4. **(Tùy chọn) Form liên hệ → CRM**: server của web chính gọi `POST /api/v1/leads` với `source: "website_contact"`.
   - WordPress: plugin form có webhook, hoặc đoạn PHP nhỏ dùng `wp_remote_post()` trong theme con.
   - Laravel: thêm 1 lệnh `Http::withHeaders([...])->post(...)` trong `ContactController@store` (sau khi lưu như hiện tại).
5. **Mạng xã hội**: giai đoạn đầu tư vấn viên nhập tay trong CRM (`source: facebook/zalo`); sau này nối webhook (Facebook Lead Ads, Zalo OA) vào `POST /leads`.

## 5b. CRM là nơi tra cứu khách

CRM **riêng tư**: chỉ nhân viên có tài khoản mới vào (không có trang công khai, `noindex`, có thể giới hạn IP văn phòng). Khi có người gọi tới, nhân viên **gõ số điện thoại (hoặc email, tên)** → mở hồ sơ khách → thấy: thông tin học sinh/phụ huynh, **kết quả và bài làm Holland** (từng câu), **toàn bộ tin nhắn với chatbot**, form liên hệ, lịch hẹn, ghi chú, xếp loại. Khách từ nhiều nguồn được **gộp theo SĐT / email**.

## 6. Hạ tầng (cùng một máy chủ Linux)

| Tên miền con | Thư mục | Ứng dụng |
|---|---|---|
| `viger.edu.vn` | web chính | WordPress hoặc Laravel của Tuấn |
| `holland.viger.edu.vn` | `/var/www/holland/public` | Laravel – Vân |
| `crm.viger.edu.vn` | `/var/www/crm/public` | Laravel – Vân |
| `chat.viger.edu.vn` | dịch vụ Python (uvicorn/gunicorn sau nginx) | Ý |

Mỗi web một database riêng, một file `.env` riêng, HTTPS cho tất cả. Sao lưu toàn bộ trước khi cài.

## 7. Gắn / gỡ một module (không sửa code)

Mỗi web là một **module rời**: cài ở tên miền con riêng, database riêng, nối với nhau chỉ bằng API ở trên.

| Việc | Làm ở đâu | Ảnh hưởng |
|---|---|---|
| **Gắn** Holland vào CRM | Trang **Cài đặt** của Holland: bật "Gửi khách sang CRM", nhập địa chỉ CRM + token do CRM cấp → bấm "Kiểm tra kết nối" | Khách làm bài tự vào CRM |
| **Gỡ** Holland | Tắt tên miền con / thu hồi token ở CRM | CRM giữ nguyên toàn bộ khách đã nhận |
| **Gỡ / thay** CRM | Tắt kết nối trong Cài đặt của Holland | Holland vẫn chạy, khách nằm chờ trong hàng đợi, gửi tiếp khi gắn CRM (mới) |
| **Gắn / thay** chatbot | CRM và Holland cấp token cho chatbot; web chính / Holland dán 1 dòng `<script>` (Holland: ô "Mã nhúng chatbot" trong Cài đặt) | Gỡ: xóa dòng script, thu hồi token |
| **Thay thuật toán Holland** | Cài engine mới (phiên bản mới của web Holland), chọn trong Cài đặt | CRM, chatbot không đổi vì kết quả vẫn đúng `holland-result/1` |
| **Gắn** mạng xã hội | Cấp token cho công cụ webhook, gọi `POST /leads` | — |
