> **ĐÃ THAY (14/11/2026):** làm theo `docs/THIET_KE_TONG_THE_V1.md` và `docs/HOP_DONG_KET_NOI.md` bản 3. File này chỉ còn để tham khảo phần dữ liệu, danh mục. Phần FIT, `lead/1`, vai trò cũ **không dùng**.

# PROMPT CLAUDE CODE – WEB 1: CRM VIGER-EDU (`crm.viger.edu.vn`)

> Làm CRM **trước** Holland, vì CRM giữ danh mục ngành và nhận khách.
> Cách dùng: tạo thư mục trống `VigerEdu_CRM` (trên ổ D), chép vào đó thư mục `database/` của gói này và cả thư mục `00_CHUNG/` (đổi tên thành `docs/`). Mở Claude Code tại thư mục đó, dán phần dưới dòng `=====`.

=====

Bạn là lập trình viên Laravel. Nhiệm vụ: tạo **web CRM riêng** cho Viger-Edu (tư vấn du học Đức), chạy ở tên miền con `crm.viger.edu.vn`. Đọc hết yêu cầu trước khi làm.

## 0. Bối cảnh

Hệ thống gồm nhiều web **tách rời**, mỗi web gỡ ra hoặc thay thế được:
- `viger.edu.vn`: web chính (Tuấn).
- `holland.viger.edu.vn`: bài test Holland (tôi làm sau, prompt riêng).
- **`crm.viger.edu.vn`: CRM – web này.** Công cụ quản trị cho tư vấn viên, là **trung tâm dữ liệu khách**: nhận khách từ Holland, AI chatbot (Python, của Ý), form liên hệ web chính, mạng xã hội.
- Các web chỉ nói chuyện qua API trong **`docs/HOP_DONG_KET_NOI.md`. Đọc file này đầu tiên. Đây là hợp đồng, không được tự đổi tên trường hay URL.** Nếu thấy cần đổi, hỏi tôi.

Tài liệu khác trong `docs/`: `Phan_tich_thiet_ke_he_thong_Holland.docx` (BFD/DFD/ERD), `3_Mau_GoogleSheet_Viger_Holland_v2.xlsx` (cấu trúc DS_KHACH, DE_XUAT, TONG_HOP, DIEM_LEAD, DM_*, CHATBOT_PHIEN), `Holland_de_xuat_nganh_v2.*` (thuật toán Holland – để hiểu dữ liệu, CRM không chấm Holland). Seed data: `database/seeders/data/crm/*.json`.

## 0a. MỤC TIÊU SỐ 1: NGƯỜI KHÔNG LÀM IT DÙNG ĐƯỢC

Đọc kỹ `docs/TIEU_CHI_DE_DUNG_VA_NGHIEM_THU.md` trước khi làm. **10 quy tắc thiết kế admin ở mục 1 là bắt buộc** cho mọi màn hình; cuối mỗi giai đoạn chuẩn bị **bản chạy thử + tài khoản mẫu theo vai trò** và liệt kê các việc trong **bảng nghiệm thu mục 3** đã làm được ở giai đoạn đó. Làm luôn: nút "?" có hướng dẫn ngắn trên mỗi trang admin, trang "Hướng dẫn sử dụng" theo vai trò, tour giới thiệu lần đăng nhập đầu, và ở giai đoạn cuối viết `docs/SO_TAY_SU_DUNG.md` (mỗi việc một mục: mục đích → các bước → lỗi thường gặp; chừa chỗ chèn ảnh chụp màn hình).

## 0a2. TỪ ĐIỂN DỮ LIỆU CHUNG (bắt buộc)

Tên trường, khóa API, mã định danh và **giá trị lựa chọn** phải đúng `docs/TU_DIEN_DU_LIEU_CHUNG.xlsx` (và `docs/lua_chon_chuan.json`). Không tự đặt tên khác. Thêm trường mới thì báo tôi để cập nhật từ điển.

## 0b. NGUYÊN TẮC BẮT BUỘC: "MỞ" – KHÔNG CỐ ĐỊNH TRONG CODE

Người quản lý (Thầy) **không làm IT**. Mọi thứ người quản lý có thể muốn thêm/đổi sau này phải **thêm/đổi được trên giao diện admin**, không sửa code, không chạy lệnh. Chỉ phần **lõi tính toán** (chấm Holland, khung bảo mật) nằm trong code. Cụ thể:

1. **Trường dữ liệu tùy biến (custom fields)** cho Khách hàng, Đề xuất, Ngành, Trường, Chương trình, Khu vực: trang "Trường thông tin" cho ADMIN **thêm / sửa / ẩn / sắp xếp** trường mới: tên hiển thị, mã (tự sinh từ tên), kiểu (chữ, đoạn văn, số, ngày, có/không, chọn 1, chọn nhiều, SĐT, email, link, tệp), danh sách lựa chọn, bắt buộc hay không, nhóm (để hiện theo khối), hiện ở danh sách / bộ lọc / form / hồ sơ / file Excel hay không. Lưu theo kiểu definitions + values (hoặc cột JSON có index) – **không tạo cột mới bằng migration**. Trường tùy biến **tự xuất hiện** ở form, hồ sơ, bộ lọc, tìm kiếm, xuất/nhập Excel, API.
2. **Các trường "gốc" cũng không cứng về hiển thị**: đổi được tên hiển thị, thứ tự, ẩn/hiện, nhóm của mọi trường có sẵn (Vai trò, GPA, Ngân sách…).
3. **Mọi danh sách lựa chọn sửa được** ở trang "Danh sách lựa chọn": nguồn khách, trạng thái chăm sóc (kèm màu, thứ tự – dùng cho bảng Kanban), trình độ hiện tại, bậc học, ngân sách, thời điểm, trình độ tiếng Anh/Đức, loại trường, nhãn (tag), lý do ngừng… Không dùng enum cứng trong code/database.
4. **Thang điểm xếp loại mở**: thêm tiêu chí mới dựa trên **bất kỳ trường nào** (kể cả trường tùy biến) với kiểu luật: ngưỡng số, tra giá trị, có/không, lấy điểm cao nhất của nhiều trường; thêm/đổi tên/màu mức xếp loại (không chỉ GREEN/YELLOW/RED).
5. **Danh mục nhiều tầng mở**: thêm quốc gia mới (ví dụ Áo, Hà Lan), khu vực, trường, chương trình, ngành, **nhóm ngành / lĩnh vực mới** – tất cả trên giao diện hoặc qua Excel.
6. **Xuất Excel theo mẫu tự chọn**: ngoài mẫu chuẩn (giống sheet mẫu), ADMIN tạo **"mẫu xuất"** riêng: chọn cột (kể cả trường tùy biến), thứ tự, tên cột, bộ lọc → lưu lại dùng nhiều lần / đặt lịch gửi tự động.
7. **Nhận trường lạ từ nguồn khác**: gói `lead/1` có thể kèm `extra_fields: {"ma_truong": giá_trị}` – CRM lưu vào trường tùy biến cùng mã; mã chưa tồn tại thì lưu tạm và hiện trong hồ sơ mục "Thông tin khác", ADMIN bấm "Tạo trường" để chính thức hóa.
8. **Phân quyền mở**: ADMIN tạo **vai trò mới** và tick quyền (xem khách, sửa khách, xem SĐT đầy đủ, xuất Excel, sửa danh mục, sửa cài đặt…), không chỉ ADMIN/CONSULTANT cố định.
9. **Test chấp nhận bắt buộc** (feature test): ADMIN thêm trường "Trường THPT chuyên?" (có/không) + thêm trạng thái "Đã ký hợp đồng" + thêm tiêu chí xếp loại dựa trên trường mới → trường hiện ở form, hồ sơ, bộ lọc, file Excel, API; trạng thái chọn được; điểm xếp loại thay đổi đúng – **không sửa một dòng code nào**.

## 0c. Bố cục admin

Xem `docs/anh_mau_quan_tri/H20_admin_crm.png` và mục 11 trong `docs/De_xuat_cau_truc_quan_tri_web_Viger.docx`: menu trái chia nhóm, đúng các mục trong hình; làm admin theo bố cục đó.

## 1. Quy tắc làm việc

1. Tạo project **Laravel 13** mới (PHP ≥ 8.3) trong thư mục hiện tại, dùng **MySQL** khi chạy thật (SQLite cho dev/test được), Blade + CSS/JS thuần (không cần Vite/Tailwind). Cài Laravel Boost (`composer require laravel/boost --dev && php artisan boost:install`) rồi theo hướng dẫn nó sinh ra.
2. Giao diện thống nhất với web chính: tham khảo màu, font, logo trong repo `https://github.com/vivi123-ou/VigerEdu_Web` (`public/styles.css`, `resources/views/layouts/app.blade.php`, `partials/header.blade.php`). Có thể clone repo đó **ra thư mục khác** để xem, **không sửa** repo đó.
3. **Không thêm package ngoài Laravel nếu chưa hỏi tôi.** Ngoại lệ đã được duyệt: **`openspout/openspout`** (đọc/ghi file Excel .xlsx, nhẹ, không cần extension lạ). Biểu đồ, email dùng thứ có sẵn.
4. Git: `git init`, nhánh `main` sạch, làm trên `dev`, commit theo giai đoạn, message tiếng Việt. Không push.
5. Làm theo **giai đoạn ở mục 8**: gửi kế hoạch → chờ tôi đồng ý → làm → `php artisan test` + `vendor/bin/pint --dirty` → báo kết quả → dừng chờ.
6. Giao diện tiếng Việt; tên class/bảng/cột tiếng Anh.
7. Tài liệu chỉ viết: `README.md` (cài đặt, deploy lên host Linux, cấu hình token), `docs/API_CRM.md` (chi tiết API, ví dụ curl + Python `requests`).
8. Chưa rõ hoặc mâu thuẫn: hỏi tôi.

## 2. Đăng nhập và phân quyền

- Toàn bộ web CRM yêu cầu đăng nhập (trừ `/api/v1/*` dùng token). Không có đăng ký công khai.
- Bảng `users`: username, full_name, email, password, role (`ADMIN` | `CONSULTANT`), active. Seeder tạo admin từ `.env` (`CRM_ADMIN_USERNAME`, `CRM_ADMIN_PASSWORD`).
- ADMIN: mọi thứ + quản lý danh mục, thang điểm, người dùng, token API. CONSULTANT: xem/chăm sóc khách, sửa đề xuất, lịch hẹn, ghi chú, xuất file.
- Bảng `api_clients`: name (holland, chatbot, website, social), token_hash (lưu hash, chỉ hiện token 1 lần khi tạo), allowed_scopes (leads:write, chat:write, catalog:read), active, last_used_at. Middleware kiểm tra `X-Api-Token` bằng `hash_equals` trên hash, throttle 60/phút.

## 3. Cơ sở dữ liệu (theo ERD + Google Sheet mẫu)

| Bảng | Tab | Cột chính |
|---|---|---|
| `customers` | DS_KHACH | code (KH0001… tự sinh), first_source, role (HS/PH), student_name, birth_year, phone, email, parent_name, parent_phone, province, current_level, current_school, gpa, english_level, german_level, desired_level, country, region, interested_major, budget, timing, consent_at; **ảnh chụp Holland mới nhất**: holland_code, holland_percent (json), holland_result (json HollandResult nguyên vẹn), holland_engine; lead_points (json), lead_total, lead_tier, lead_meaning; appointment_at, status (Chờ gọi / Đã gọi / Đã hẹn / Đang làm hồ sơ / Ngừng), consultant_id, note |
| `customer_events` | lịch sử | customer_id, source, type (lead_received, quiz_completed, chat_session, appointment, status_changed, note), external_ref, payload (json nguyên gói nhận được, có schema_version), occurred_at |
| `recommendations` | DE_XUAT | customer_id, priority, major_code, program_code, fit, label, reason, type (Chính/Thay thế), edited_by_consultant, note |
| `chat_sessions` | CHATBOT_PHIEN | các trường trong mục 3.2 của hợp đồng + customer_id |
| `chat_messages` | (tin nhắn) | chat_session_id, at, role (user/assistant/agent), text – lưu **toàn bộ hội thoại** chatbot gửi sang; nối thêm theo `session_code`, không trùng |
| `quiz_attempts` | (bài làm) | customer_id, source, attempt_id (unique), completed_at, question_set_version, holland_code, result (json), answers (json: code, group, text, value, max), view_url |
| `lead_criteria`, `lead_options`, `lead_tiers` | DIEM_LEAD | key, label, rule (threshold_gte / lookup / max_of_english_german) / value, points / min_points, label, meaning, action |
| `countries`, `regions`, `schools`, `programs` | DM_* | như sheet mẫu (code, is_active, priority, cost_level, city, type, min_gpa, language, language_requirement, tuition_eur, intake, note …) |
| `majors` | DM_NGANH | code (N001…), type (university/vocational), name_vi, name_de, name_en, field, testas_primary, testas_secondary, holland_code_1..3, code_source, description, subjects, careers, suitable_for, school_types, languages, keywords, is_active |

Seeder idempotent (`updateOrCreate` theo `code`) đọc `database/seeders/data/crm/`: `countries.json` (3), `regions.json` (17: 16 bang có thủ phủ, dân số, diện tích, GDP/người, lĩnh vực nổi bật, mô tả + Wien tắt), `schools.json` (390 trường – danh sách HRK do Ý thu thập, mã T + mã HRK 3 số, có hrk_id, loại trường, hình thức, bang, thành phố, địa chỉ, SĐT, website, số sinh viên, quyền tiến sĩ), `programs.json` (30, trường `major_id` là mã ngành), `majors.json` (75 – map: `id→code`, `loai` "Đại học"/"Học nghề" → type, `ten→name_vi`, `ten_de`, `ten_en`, `linh_vuc→field`, `testas1/2`, `codes[0..2]→holland_code_1..3`, `nguon→code_source`, `mo_ta→description`, `mon_hoc→subjects`, `nghe→careers`, `hop_voi→suitable_for`, `truong→school_types`, `ngon_ngu→languages`, `tu_khoa→keywords`, `is_active`), `lead_scoring.json`. Dữ liệu trường/chương trình là **VÍ DỤ** – giữ ghi chú "cần kiểm chứng".

## 4. Nghiệp vụ

**`CustomerIntakeService`** (dùng cho API `/leads` và nhập tay): validate theo hợp đồng `lead/1` (consent bắt buộc; phải có phone hoặc email) → tìm khách theo phone hoặc email (chuẩn hóa SĐT: bỏ khoảng trắng, +84→0) → tạo/cập nhật (không xóa dữ liệu cũ bằng giá trị rỗng) → ghi `customer_events` → nếu có `holland_result` thì lưu ảnh chụp → nếu có `quiz` thì lưu `quiz_attempts` (mỗi lần làm bài một dòng, giữ lịch sử nhiều lần làm) → tính lead → sinh đề xuất trường → trả `{customer_code, is_new, lead_tier, handoff_now}` (`handoff_now` = GREEN). Gửi lặp cùng `source + external_ref` thì **không tạo trùng** (idempotent).

**`LeadScoringService`** (đọc bảng, không hard-code): gpa = điểm của mức `value` lớn nhất ≤ GPA; language = max(điểm tiếng Anh, điểm tiếng Đức); budget/timing/decider (theo role: PH/HS) = tra đúng giá trị; action = "Đặt lịch" nếu có appointment_at, "Để lại SĐT" nếu có phone/parent_phone, còn lại "Chỉ email". Tổng → tier theo min_points giảm dần. **Test: KH0001 (PH, GPA 8.2, IELTS 6.0+, Đức A1-A2, 300-500 triệu, 1-2 năm, Đặt lịch) = 2+2+1.5+1+1+1.5 = 9 → GREEN.**

**`ProgramMatchingService`** (DE_XUAT trường, chỉ dùng dữ liệu trong CRM): lấy `holland_result.top_university` của khách → các chương trình active của ngành đó, cùng bậc khách muốn (nếu có), trường/khu vực/quốc gia active → `score = fit − penalty_region[priority] (1: 0 · 2: 0.02 · 3: 0.05) + 0.05 nếu trùng ngành quan tâm`. GPA < min_gpa − 0.5 → loại; [min_gpa − 0.5, min_gpa) → "Thay thế"; còn lại "Chính". Tối đa 4 Chính + 2 Thay thế. Không ghi đè dòng `edited_by_consultant`. Số liệu để trong `config/crm.php`.

## 5. Giao diện quản trị

- **Tổng quan (TONG_HOP):** tổng khách, GREEN/YELLOW/RED, đã đặt lịch, khách mới 7 ngày, theo nguồn, theo chữ đầu mã Holland, top ngành/trường được đề xuất.
- **Ô tìm kiếm nhanh luôn ở đầu mọi trang**: gõ SĐT (chấp nhận có/không khoảng trắng, +84, dấu chấm), email hoặc tên → gợi ý ngay → Enter mở **hồ sơ khách**. Tìm cả trong SĐT phụ huynh. Đây là thao tác chính khi có khách gọi đến.
- **Danh sách khách:** lọc xếp loại/nguồn/mã Holland/trạng thái/tư vấn viên/ngày; tìm tên/SĐT/email; GREEN lên đầu; phân trang; nút "Thêm khách" (nhập tay, chọn nguồn facebook/zalo/điện thoại…).
- **Chi tiết khách:** thông tin; **kết quả Holland** (6 điểm %, mã, độ rõ/nhất quán/bằng điểm kèm câu giải thích, top ngành với khớp mã, khớp điểm, FIT, lý do – đọc từ ảnh chụp JSON, hiển thị được cả khi thiếu một số trường); **tab "Bài test Holland"** (tư vấn viên thấy ĐẦY ĐỦ, khác trang kết quả của khách chỉ hiện ~3 ngành không có điểm): nút **"Xem xếp hạng đầy đủ"** gọi `POST {HOLLAND_URL}/api/v1/score?full=1` với 6 điểm % đã lưu → bảng toàn bộ ngành (lọc lĩnh vực / đại học / học nghề, sắp theo FIT; Holland không kết nối được thì hiện top đã lưu và báo); mỗi lần làm bài – ngày, mã, biểu đồ 6 nhóm, **bảng từng câu và câu trả lời**, link xem bài gốc; **tab "Chatbot"**: danh sách phiên, mở ra xem **toàn bộ tin nhắn dạng khung chat** + tóm tắt AI + lý do chuyển tư vấn; **dòng thời gian** gộp mọi sự kiện từ mọi nguồn (test, chat, form liên hệ, cuộc gọi, ghi chú) theo thời gian; nút **"Ghi nhận cuộc gọi"** (thời gian, nội dung, kết quả) và **gộp 2 hồ sơ trùng** (chọn hồ sơ chính, chuyển hết sự kiện sang); đề xuất trường (sửa/thêm/xóa/ghi chú); điểm lead từng tiêu chí; lịch hẹn, trạng thái, tư vấn viên phụ trách, ghi chú.
- **Danh mục (ADMIN):** ngành (kiểm tra mã Holland: 3 chữ thuộc RIASEC, không lặp, ít nhất 1 mã), trường, chương trình, khu vực, quốc gia, thang điểm lead, người dùng, khóa API. Ngừng dùng = tắt `is_active`, không xóa.
### 5.1 Xuất / nhập Excel – ưu tiên "Thầy dùng được ngay, không cần hướng dẫn"

Thầy quen làm việc trên Excel / Google Sheet, nên:
- **Nút "Xuất Excel" một chạm** ở trang Tổng quan và trang Danh sách khách: tải **1 file `.xlsx` nhiều tab, giống hệt bố cục file `docs/3_Mau_GoogleSheet_Viger_Holland_v2.xlsx`**:
  - `DS_KHACH`: hàng 1 là tên nhóm cột, hàng 2 là tên cột tiếng Việt **đúng thứ tự và đúng tên cột như sheet mẫu**; ô Xếp loại **tô màu xanh / vàng / đỏ** theo GREEN / YELLOW / RED; dòng tiêu đề cố định (freeze), có bộ lọc (autofilter), cột vừa chữ.
  - `DE_XUAT`: đúng cột như sheet mẫu.
  - `TONG_HOP`: các con số của trang Tổng quan (giá trị tĩnh, không công thức).
  - `CHATBOT_PHIEN`: đúng cột như sheet mẫu.
  - Tên file: `CRM_Viger_2026-11-03.xlsx`. Xuất **theo bộ lọc đang chọn** (ví dụ chỉ GREEN, chỉ tháng này) hoặc toàn bộ.
  - Mở bằng Excel hay kéo thả vào Google Drive (mở bằng Google Sheets) đều đúng tiếng Việt, đúng định dạng ngày (dd/mm/yyyy) và số.
- **Xuất / nhập danh mục bằng Excel** (ADMIN): nút "Tải danh mục" → 1 file `.xlsx` có các tab `DM_QUOC_GIA`, `DM_KHU_VUC`, `DM_TRUONG`, `DM_CHUONG_TRINH`, `DM_NGANH`, `DIEM_LEAD` (cột như sheet mẫu, thêm cột mã Holland 1–3 cho ngành). Thầy sửa trong Excel rồi bấm "Nhập danh mục" để tải lên:
  - Bước **xem trước**: hiện số dòng thêm mới / cập nhật / lỗi (lỗi ghi rõ tab, dòng, cột, lý do – ví dụ "DM_NGANH dòng 12: mã Holland 'ABX' không hợp lệ") **trước khi** ghi vào database.
  - Khớp theo cột Mã (code): có rồi thì cập nhật, chưa có thì thêm; **không bao giờ xóa** dòng không có trong file (chỉ tắt bằng cột `is_active` = 0).
  - Mỗi lần nhập được lưu lịch sử (ai, lúc nào, file gốc) để hoàn tác bằng cách nhập lại file cũ.
- Giữ thêm **xuất CSV UTF-8 có BOM** cho DS_KHACH (dùng khi cần nhập vào công cụ khác).

## 6. API (`/api/v1`, đúng hợp đồng)

`POST /leads` (scope leads:write), `POST /chat-sessions` (chat:write – lưu phiên + **nối thêm tin nhắn** theo `session_code`; gắn khách theo `customer_code`, hoặc theo `phone`/`email` nếu có, hoặc để "chưa gắn khách" và tư vấn viên gắn tay sau), `GET /catalog/majors` (catalog:read; có cache 10 phút, xóa cache khi sửa ngành). Lỗi trả JSON 401/403/422/429. Viết `docs/API_CRM.md` kèm ví dụ curl và Python `requests`.

## 7. Bổ sung: bật/tắt, kết nối và cài đặt KHÔNG cần sửa code

- **Trang "Cài đặt" trong admin** (ADMIN), lưu trong bảng `settings` (key/value, có cache), **ưu tiên hơn `.env`** cho mọi thứ người quản lý cần đổi: tên đơn vị, logo, màu chủ đạo, hotline, Zalo, email gửi đi, link web chính, mã Google Analytics / Google Search Console, và **phần kết nối** (bật/tắt từng kết nối, địa chỉ web bên kia, token, nút "Kiểm tra kết nối" gọi thử API và báo xanh/đỏ). Không ghi cứng tên miền hay chữ "Viger" trong code/view – lấy từ cài đặt.
- **Gỡ / thay một công cụ = tắt kết nối trong trang Cài đặt** (hoặc thu hồi token), không sửa code. Công cụ còn lại tự chạy chế độ dự phòng.
- **SEO:** title + meta description từng trang (sửa được trong Cài đặt), Open Graph, `sitemap.xml`, `robots.txt`; trang công khai có H1/H2 đúng cấu trúc; trang kết quả cá nhân và toàn bộ trang admin đặt `noindex`; chèn mã Analytics từ Cài đặt.
- **Cài lên host Linux trắng (VPS) do nhóm tự quản lý:** README có mục từng bước: cài nginx, PHP-FPM 8.3 + extension cần thiết, MySQL/MariaDB, Composer, tạo database + user riêng, tạo server block cho tên miền con trỏ vào `public/`, chứng chỉ HTTPS bằng Certbot, quyền thư mục `storage/` `bootstrap/cache/`, cron `* * * * * php artisan schedule:run`, supervisor (hoặc systemd) chạy queue worker; mỗi web chạy bằng user Linux riêng; cách cập nhật phiên bản mới (`git pull`, `composer install --no-dev`, `php artisan migrate --force`, xóa cache) và cách quay lại bản trước. Kèm file mẫu cấu hình nginx trong `deploy/`.
- **Trang cài đặt lần đầu** `/install` (tự khóa sau khi xong): kiểm tra yêu cầu máy chủ (PHP, extension, quyền ghi), nhập database, tạo admin, nạp dữ liệu mẫu, sinh token.

## 7b. CRM riêng tư và xuất dữ liệu tự động

- **Riêng tư:** không có trang công khai nào (trang chủ = đăng nhập), toàn site `noindex` + `robots.txt` chặn hết; đăng nhập sai 5 lần khóa 15 phút; tùy chọn **chỉ cho phép IP văn phòng** (danh sách IP sửa trong Cài đặt); tùy chọn **xác thực 2 lớp bằng mã gửi email** cho ADMIN; nhật ký truy cập (ai xem hồ sơ khách nào, lúc nào); ẩn bớt số điện thoại trong danh sách (hiện đủ khi mở hồ sơ).
- **Xuất tự động:** trong Cài đặt chọn lịch (hằng ngày / hằng tuần, giờ gửi) và người nhận → hệ thống tự tạo file Excel (mục 5.1) và **gửi email đính kèm** + lưu bản trong mục "File đã xuất" để tải lại. Tùy chọn **đồng bộ Google Sheet** (ghi đè tab DS_KHACH, DE_XUAT của một Google Sheet định sẵn bằng service account): **làm sau cùng và hỏi tôi trước** vì cần thư viện Google; nếu không dùng thư viện thì tự ký JWT bằng `openssl` + `Http`.

## 8. Giai đoạn

1. Khởi tạo Laravel, Boost, layout + đăng nhập, users/roles, seeder admin. Xong khi đăng nhập được.
2. Migrations + models + **hệ thống trường tùy biến + danh sách lựa chọn + vai trò/quyền (mục 0b)** + seeder danh mục (đếm đúng: 75 ngành, 390 trường, 30 chương trình, 17 khu vực, 3 quốc gia, 6 tiêu chí lead).
3. Ba service ở mục 4 + unit test (KH0001 = 9 GREEN; khớp khách trùng SĐT; idempotent external_ref; matching chương trình theo GPA).
4. Giao diện quản trị (mục 5, gồm ô tìm theo SĐT, tab Bài test, tab Chatbot, gộp hồ sơ) + test: tìm "0912 345 678" / "+84912345678" ra cùng khách; phiên chat gửi 2 lần → tin nhắn nối thêm, không trùng; test phân quyền (chưa đăng nhập → login; CONSULTANT không vào được danh mục).
5. API + `api_clients` + test (sai token 401; scope sai 403; gửi `lead/1` mẫu trong hợp đồng → tạo KH, tier GREEN; gửi lại → không trùng; `catalog/majors` trả 75 ngành active).
6. Xuất/nhập Excel (mục 5.1) + test: file xuất mở lại bằng openspout có đúng tên tab, đúng thứ tự cột hàng 2 như sheet mẫu, ô Xếp loại có màu; nhập file có dòng sai → xem trước báo lỗi đúng dòng, không ghi gì; nhập file đúng → cập nhật, không xóa dòng thiếu.
7. **An toàn dữ liệu khi gỡ / cài lại / cập nhật**:
   - Migration chỉ **thêm** (không có lệnh xóa bảng/cột trong bản cập nhật); README ghi rõ **tuyệt đối không chạy `migrate:fresh` / `migrate:reset` / `db:wipe` trên host thật**; trong production, chặn các lệnh này (kiểm tra `app()->environment('production')` và báo lỗi).
   - Lệnh `php artisan crm:backup`: xuất database (mysqldump nếu có, nếu không thì xuất toàn bộ bảng ra 1 file `.xlsx` + `.json`) vào `storage/app/backups/` có ngày giờ, giữ 30 bản gần nhất; chạy tự động mỗi đêm bằng scheduler; trang admin có nút "Sao lưu ngay" và "Tải bản sao lưu".
   - Lệnh `php artisan crm:restore {file}` (chỉ ADMIN, có hỏi xác nhận) và test khôi phục.
   - README có mục **"Gỡ CRM / cài lại CRM"**: gỡ = tắt tên miền con và token API (web Holland tự giữ khách trong hàng đợi); cài lại = cài code, khôi phục bản sao lưu, bật token. Ghi rõ: các web khác **không bị ảnh hưởng database** vì mỗi web một database riêng.
8. README (deploy lên host Linux: PHP 8.3, MySQL, document root `public/`, quyền `storage/`, HTTPS, cron cho scheduler) + chạy toàn bộ test. Báo cáo: file đã tạo, việc còn mở, câu hỏi cho tôi/Thầy.

Mục 7 làm xen vào các giai đoạn phù hợp (Cài đặt + kết nối ở giai đoạn kết nối/API; SEO ở giai đoạn giao diện; /install và tài liệu VPS ở giai đoạn cuối).

Bắt đầu bằng việc đọc `docs/HOP_DONG_KET_NOI.md` và các JSON seed, rồi gửi tôi kế hoạch giai đoạn 1.
