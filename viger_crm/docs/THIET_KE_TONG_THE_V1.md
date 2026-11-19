# THIẾT KẾ TỔNG THỂ V1 – VIGER EDU (Web chính · Holland · CRM · Chatbot)

> Bản chốt trước khi xây dựng, ngày 14/11/2026. File này giống nhau ở repo `VigerEdu_Holland` và `VigerEdu_CRM`.
> Khi có mâu thuẫn với tài liệu cũ (PROMPT 1–3, CLAUDE.md cũ), **làm theo file này**.
> Mẫu giao diện: `docs/mau_giao_dien_v3/index.html` (mở bằng trình duyệt; thu nhỏ cửa sổ để xem bản điện thoại).

---

## 0. Quyết định đã chốt

| # | Quyết định | Ghi chú |
|---|---|---|
| Q1 | **Khách làm bài trước, điền thông tin sau** để xem báo cáo đầy đủ | Bỏ quyết định 11/11 "điền thông tin trước" |
| Q2 | **OTP qua email** | Số điện thoại vẫn bắt buộc nhưng chưa xác thực (`phone_verified = false`). Chừa sẵn chỗ để sau này thêm SMS/Zalo |
| Q3 | Làm bài **6 câu một trang**, chấm bằng **ngôi sao 1–5** (kiểu UEH), 6 trang | Bỏ quyết định "1 câu một màn hình", "ô số" |
| Q4 | **3 vai trò: Quản trị viên > Tư vấn viên > Nhân viên.** Trưởng nhóm tư vấn và tư vấn viên gộp làm một | Vai trò là dữ liệu, quản trị viên thêm được vai trò mới (ví dụ Marketing) mà không sửa code |
| Q5 | **Chỉ quản trị viên** được xem toàn bộ khách và **xuất dữ liệu** | Chống nhân viên sao chép danh sách khách (mục 6.3) |
| Q6 | **CRM là hệ thống nội bộ riêng** (`crm.viger.edu.vn`), không có trang công khai | Holland chỉ là nơi khách làm bài |
| Q7 | **Đa kênh:** khách vào CRM từ Holland, web chính, chatbot, Facebook, Zalo OA, sự kiện, nhập tay | Mỗi kênh một khóa API, bật/tắt riêng |
| Q8 | **Logo và màu theo viger.edu.vn:** navy `#12263F`, đỏ `#C1121F`, vàng `#FFB703`, nền kem `#FDFBF7`; font Inter (chữ), Playfair Display (tiêu đề trang khách) | Lấy từ `public/css/styles.css` và `public/img/logo.png` của repo VigerEdu_Web |
| Q9 | Code **chia module, có chú thích tiếng Việt**, không gom một khối; mỗi ứng dụng gỡ/thay được | Mục 3 |

---

## 1. Brainstorm: vấn đề hiện tại và hướng giải quyết

| Vấn đề | Hướng giải quyết |
|---|---|
| Tài liệu và code lệch nhau (thứ tự luồng, OTP, vai trò, FIT cũ trong CRM) | Một file thiết kế chung (file này) + hợp đồng API v3; CLAUDE.md hai repo trỏ về đây |
| Công cụ AI sửa "tùm lum", khó kiểm soát | Mỗi giai đoạn nhỏ, có "Xong khi"; chỉ làm 1 giai đoạn mỗi lần; bắt buộc test + báo cáo trước khi commit |
| Giao diện chưa chuyên nghiệp, chưa đồng bộ thương hiệu | Hệ thống thiết kế dùng chung (mục 7), tham khảo Haravan, Shopify Polaris, HubSpot, Pipedrive |
| Dữ liệu khách nằm rải rác | CRM là nơi duy nhất giữ hồ sơ khách; các ứng dụng khác chỉ gửi vào qua API |
| Nhân viên có thể lấy cắp danh sách khách | Phân quyền theo phạm vi dữ liệu, chỉ admin xuất, nhật ký xem/xuất, giới hạn tốc độ xem hồ sơ |
| Sau này muốn thêm kênh, thêm trường, thêm vai trò | Kênh, trường thông tin, trạng thái, vai trò, tiêu chí xếp loại đều là **dữ liệu sửa trên admin** |
| Sợ web chậm khi gắn nhiều thứ | Không SPA; CSS/JS tách theo khu vực; phân trang; hàng đợi cho việc gửi CRM/email; chỉ mục CSDL |
| Sợ bị tấn công | Mục 8: Cloudflare, khóa đăng nhập, CSRF, mã hóa SĐT, token băm, log không chứa dữ liệu cá nhân |

---

## 2. Kiến trúc tổng thể

```
                     Khách (điện thoại / máy tính)
                                │
           ┌────────────────────┼────────────────────┐
           ▼                    ▼                    ▼
   viger.edu.vn          holland.viger.edu.vn    chat.viger.edu.vn
   (Web chính, Tuấn)     (Holland, Vân)          (Chatbot Python, Ý)
   nút "Làm trắc nghiệm" → làm bài, OTP email     hỏi đáp, gợi ý gặp tư vấn
   form liên hệ          báo cáo khách
           │                    │                    │
           │  POST /api/v1/leads (khóa kênh web)     │  POST /api/v1/leads (khóa kênh chatbot)
           │                    │  POST /api/v1/leads + /events (khóa kênh holland)
           ▼                    ▼                    ▼
                  crm.viger.edu.vn  (CRM nội bộ, Vân)  ◄── Facebook Lead Ads, Zalo OA (webhook)
                  chỉ nhân viên đăng nhập · noindex · có thể giới hạn IP văn phòng
                  khách hàng · hành trình · gọi điện · lịch hẹn · báo cáo · xuất dữ liệu
```

Nguyên tắc:

1. **4 ứng dụng độc lập**, mỗi cái một repo, một CSDL, một file `.env`. Không ứng dụng nào đọc CSDL của ứng dụng khác.
2. **Nói chuyện chỉ qua API** trong `docs/HOP_DONG_KET_NOI.md` (bản 3). Thay một ứng dụng chỉ cần giữ đúng API.
3. **CRM sở hữu dữ liệu khách.** Holland giữ bài làm chi tiết và gửi bản tóm tắt + bài làm sang CRM.
4. **Gỡ được:** tắt kết nối CRM trong Holland thì khách vẫn làm bài, hồ sơ chờ trong hàng đợi.
5. **Cùng một bộ máy chủ** (Cloud Server PA, Linux), mỗi ứng dụng một tên miền con, đặt sau Cloudflare.

| Ứng dụng | Ai dùng | Đăng nhập | Ghi chú |
|---|---|---|---|
| Web chính | Khách | Không | Chỉ thêm nút/khối dẫn sang Holland, gắn chatbot, gửi form liên hệ sang CRM |
| Holland – trang khách | Khách | OTP email để mở báo cáo | `noindex` cho trang kết quả, báo cáo |
| Holland – `/admin` | Quản trị viên (đủ), tư vấn viên (xem lượt làm bài) | Tài khoản Holland | Quản lý câu hỏi, danh mục, nội dung trang, kết nối CRM |
| CRM | Quản trị viên, tư vấn viên, nhân viên | Tài khoản CRM | Toàn bộ công việc với khách |
| Chatbot | Khách | Không | Gửi khách và phiên chat sang CRM |

> Tài khoản Holland và CRM **tách riêng** ở V1 (đơn giản, an toàn). V2 có thể dùng chung đăng nhập (SSO) nếu cần.

---

## 3. Cấu trúc code: chia module, dễ tách, dễ đọc

### 3.1 Bố cục trong mỗi repo Laravel

```
app/
  Modules/                      ← mỗi nghiệp vụ một thư mục, gỡ/thêm không ảnh hưởng module khác
    Assessment/                 (Holland) rút đề, chấm, phân tích, gợi ý ngành
      Services/ Models/ Data/ README.md
    Respondent/                 (Holland) form thông tin, OTP email, đồng ý
    CrmSync/                    (Holland) hàng đợi gửi CRM, job, lệnh thử lại
    Tracking/                   (Holland) ghi sự kiện hành vi, gửi kèm sang CRM
    Contacts/                   (CRM) khách hàng, gộp trùng, trường tùy biến
    Intake/                     (CRM) nhận khách từ mọi kênh, chuẩn hóa, idempotent
    Channels/                   (CRM) kênh, khóa API, phân giao tự động
    Pipeline/                   (CRM) giai đoạn, xếp loại GREEN/YELLOW/RED
    Activities/                 (CRM) cuộc gọi, ghi chú, lịch hẹn, hành trình
    Catalog/                    (CRM) trường, ngành, chương trình, khu vực
    Reports/ Exports/           (CRM) báo cáo, xuất Excel có nhật ký
    Access/                     (cả hai) vai trò, quyền, nhật ký truy cập
  Http/Controllers/Admin/<Module>/...   ← controller mỏng: nhận request → gọi Service → trả view
  Policies/                     ← mọi kiểm tra quyền nằm ở đây
config/modules.php              ← bật/tắt module: tắt thì ẩn menu và route
routes/modules/<module>.php     ← route của từng module, nạp theo config/modules.php
resources/views/<module>/...    ← view theo module
public/css/  core.css (biến màu, chữ, nút, bảng) + admin.css + public.css   ← tách theo khu vực
public/js/   admin.js + quiz.js                                            ← chỉ nạp ở trang cần
```

Quy tắc:

- **Controller mỏng, Service dày.** Logic nghiệp vụ chỉ ở `Services`, có test riêng.
- **Một module không gọi thẳng model của module khác**; gọi qua Service công khai của module đó.
- **Không ghi cứng** chữ, ngưỡng, lựa chọn, tên miền trong code. Lấy từ bảng `settings`, danh mục, hoặc `config/`.
- **Không thêm package** ngoài danh sách đã duyệt (`openspout/openspout`) khi chưa hỏi Vân.

### 3.2 Quy ước chú thích (bắt buộc)

Đầu mỗi class, viết PHPDoc tiếng Việt 3 dòng:

```php
/**
 * Làm gì:   Chấm điểm một lượt làm bài: trung bình nhóm, xếp hạng, mã 2/3 chữ, mã thay thế.
 * Ai gọi:   SubmitAnswersController sau khi khách nộp trang cuối; API POST /api/v1/score.
 * Dữ liệu:  đọc responses, settings (ngưỡng); ghi session_results. Không đọc/ghi dữ liệu cá nhân.
 */
final class ScoringService
```

- Hàm công khai có PHPDoc mô tả tham số, giá trị trả về, ví dụ nếu khó hiểu.
- Đoạn thuật toán phức tạp (rút đề Efraimidis–Spirakis, chỉ số C) có chú thích từng bước, dẫn nguồn.
- Mỗi module có `README.md` 10–20 dòng: làm gì, bảng nào, route nào, test nào.

---

## 4. Dữ liệu

### 4.1 CSDL Holland

| Nhóm | Bảng | Ghi chú |
|---|---|---|
| Danh mục | `riasec_types`, `riasec_pairs`, `third_letters`, `code_overrides`, `majors`, `major_groups`, `occupations`, `major_occupation`, `environments` | Không có dữ liệu cá nhân |
| Câu hỏi | `question_facets`, `questions`, `question_weight_audits` | 300 câu, 60 chủ đề, trọng số có lịch sử |
| Cài đặt | `settings`, `test_config_versions` | Mọi ngưỡng, số câu/trang, nhãn sao, chữ trên trang |
| Bài làm | `test_sessions`, `session_questions`, `responses`, `session_results` | Gắn `respondent_id` (có thể rỗng); không chứa tên/SĐT |
| Người làm bài | `respondents` (SĐT, email, họ tên **mã hóa** + `phone_hash`, `email_hash`), `consents`, `otp_codes` | Chỉ tạo sau khi khách điền form |
| Trường thông tin | `form_fields` (thêm cột `crm_key`), `field_values` | Admin thêm trường mới không cần migration |
| Hành vi | `tracking_events` (session_id, type, page, meta json, occurred_at) | Ví dụ: `quiz_started`, `page_completed`, `quiz_finished`, `report_viewed`, `cta_call_clicked`, `cta_zalo_clicked` |
| Gửi CRM | `crm_outbox` (event_id uuid, payload mã hóa, status, tries, next_try_at, last_error không có dữ liệu cá nhân) | |
| Hệ thống | `users`, `roles`, `permissions`, `role_permission`, `admin_activities` (nhật ký) | |

### 4.2 CSDL CRM

| Nhóm | Bảng | Ghi chú |
|---|---|---|
| Khách | `contacts` (một người: HS hoặc phụ huynh; SĐT/email mã hóa + hash), `contact_relations` (HS ↔ phụ huynh), `possible_duplicates` | Gộp theo `customer_code` → SĐT → email; trùng SĐT khác tên + năm sinh thì đánh dấu, không tự gộp |
| Nhu cầu | `leads` (một lần có nhu cầu tư vấn: contact, channel, stage, priority, assigned_to), `lead_stage_history` | Một khách nhiều lead |
| Kênh | `channels` (code, tên, loại api/webhook/import/manual, bật/tắt, quy tắc giao), `api_clients` (token băm, scope, channel_id), `integration_events` (event_id đã nhận) | Thêm kênh = thêm dòng |
| Hồ sơ tư vấn | `assessments` (tóm tắt Holland), `counseling_profiles` (GPA, ngoại ngữ, ngân sách, thời điểm, người quyết định, lộ trình), `chat_sessions`, `chat_messages` | |
| Hành trình | `contact_events` (mọi sự kiện từ mọi kênh, hiện thành dòng thời gian), `activities` (gọi, ghi chú, tin nhắn), `appointments` | |
| Mở rộng | `field_definitions`, `field_values`, `option_lists`, `option_items`, `pipeline_stages`, `scoring_criteria`, `tags` | Admin sửa trên giao diện |
| Danh mục | `countries`, `regions`, `schools` (390), `majors`, `programs` | Seed từ `database/seeders/data/crm/` |
| Truy cập | `users`, `roles`, `permissions`, `role_permission`, `audit_logs` (ai xem/sửa hồ sơ nào), `export_logs` (ai xuất, lý do, số dòng) | |

Quy tắc dữ liệu chung:

- Khóa ngoại có chỉ mục; cột tìm kiếm (`phone_hash`, `email_hash`, `customer_code`, `created_at`, `stage`, `assigned_to`) có chỉ mục.
- **Không xóa cứng** khách: xóa mềm (`deleted_at`) và chỉ admin khôi phục.
- Từ khi chạy thật: **migration chỉ thêm** bảng/cột cho phép rỗng.
- Tên bảng, cột tiếng Anh; nhãn hiển thị tiếng Việt lấy từ danh mục.

---

## 5. Luồng chính

### 5.1 Khách làm bài (Holland)

1. Vào từ web chính (nút "Làm trắc nghiệm") → trang giới thiệu.
2. Làm bài: 6 trang × 6 câu, ngôi sao 1–5, bắt buộc trả lời đủ trang mới sang trang sau; lưu tạm trên máy.
3. Xong → **kết quả sơ lược**: mã 2 chữ, câu mô tả, 2 ngành đầu, phần còn lại làm mờ.
4. Bấm "Xem báo cáo đầy đủ" → form: họ tên, SĐT, năm sinh, email, ô đồng ý (+ phụ huynh đồng ý nếu dưới 18 tuổi), Turnstile.
5. Nhận **mã OTP qua email** (6 số, hiệu lực 5 phút, sai 5 lần khóa 30 phút) → mở **báo cáo đầy đủ**; email kèm link báo cáo.
6. Nền: tạo `respondent` → đưa hồ sơ + kết quả + bài làm + sự kiện hành vi vào `crm_outbox` → gửi CRM.

### 5.2 CRM xử lý

1. Nhận gói `lead/2` → kiểm tra khóa kênh, `event_id` → chuẩn hóa → tìm khách → tạo/cập nhật contact + lead → ghi `contact_events`.
2. Tính ưu tiên GREEN/YELLOW/RED theo `scoring_criteria` → giao người phụ trách theo quy tắc của kênh.
3. **Nhân viên** thấy khách trong "Cần gọi" (7 ngày) → gọi, ghi kết quả → hẹn lịch.
4. **Tư vấn viên** mở hồ sơ khách được giao: Holland đầy đủ, hành trình, ghi chú, lịch hẹn → chuyển giai đoạn.
5. **Quản trị viên** theo dõi tổng quan, giao lại khách, gộp trùng, xuất báo cáo.

Giai đoạn lead (sửa được): Mới → Đã liên hệ → Đã hẹn → Đang tư vấn → Tiềm năng → Đăng ký; đóng: Không liên lạc được / Số sai / Từ chối / Không có nhu cầu.

---

## 6. Phân quyền

### 6.1 Ma trận (CRM)

| Chức năng | Quản trị viên | Tư vấn viên | Nhân viên |
|---|---|---|---|
| Tổng quan, báo cáo | Toàn công ty | Của mình | – |
| Danh sách khách | Toàn bộ | Khách được giao | Khách 7 ngày gần nhất (để gọi) |
| Xem SĐT, email | ✓ | ✓ (khách được giao) | ✓ (khách trong danh sách gọi) |
| Kết quả Holland đầy đủ | ✓ | ✓ | – |
| Gọi điện, ghi kết quả | ✓ | ✓ | ✓ |
| Ghi chú tư vấn, lịch hẹn | ✓ | ✓ | Chỉ đặt lịch |
| Giao khách, gộp trùng | ✓ | – | – |
| **Xuất Excel, tải dữ liệu** | **✓ (ghi lý do, có nhật ký)** | – | – |
| Kênh, danh mục, cài đặt, tài khoản, nhật ký | ✓ | – | – |

Holland `/admin`: quản trị viên toàn quyền; tư vấn viên chỉ xem "Lượt làm bài" và báo cáo nội bộ; vai trò Marketing (nếu bật) chỉ sửa nội dung trang.

### 6.2 Cách lập trình

- Bảng `roles`, `permissions`, `role_permission`; quyền là chuỗi như `contacts.view_all`, `contacts.view_assigned`, `contacts.export`.
- Kiểm tra bằng **Policy + Gate** của Laravel; truy vấn danh sách luôn đi qua **scope** theo vai trò (`Contact::visibleTo($user)`), không lọc ở view.
- Menu đọc quyền: mục không có quyền **ẩn hẳn**; gõ thẳng đường dẫn trả về 403.
- Quản trị viên **cấp tài khoản** (email công ty, mật khẩu tạm buộc đổi lần đầu), khóa/mở, đặt vai trò; không ai tự đổi vai trò của mình.

### 6.3 Chống sao chép danh sách khách

- Không có nút xuất, chọn tất cả, in danh sách cho tư vấn viên và nhân viên.
- Danh sách 20 dòng/trang; SĐT trong danh sách hiện đủ chỉ khi cần gọi, các chỗ khác che `0819 xxx 456`.
- Giới hạn tốc độ: quá 60 hồ sơ/giờ thì tạm khóa xem và báo quản trị viên.
- Nhật ký mọi lần mở hồ sơ, xem SĐT, xuất file; trang "Nhật ký truy cập" cho quản trị viên.
- File xuất có tên người xuất trong tên file và dòng đầu, link tải hết hạn 24 giờ, tùy chọn che SĐT/email.

---

## 7. Giao diện

### 7.1 Tham khảo

- **Haravan, Shopify Polaris:** bố cục admin (thanh trên tối, menu trái sáng, bảng có tab bộ lọc lưu sẵn, huy hiệu trạng thái).
- **HubSpot CRM, Pipedrive:** hồ sơ khách 360 độ, dòng thời gian hoạt động, giai đoạn bán hàng.
- **Odoo CRM:** phân quyền theo nhóm, nhật ký thay đổi.
- **16personalities, Truity, UEH "Hiểu về bạn":** trang làm bài và kết quả trắc nghiệm.

### 7.2 Hệ thống thiết kế (dùng chung, nằm ở `public/css/core.css`)

| Thành phần | Quy định |
|---|---|
| Màu | Navy `#12263F` (chính), đỏ `#C1121F` (nút chính trang khách, chỉ báo đang chọn, cảnh báo), vàng `#FFB703` (ngôi sao, điểm nhấn), nền kem `#FDFBF7` (trang khách) và `#F4F3EF` (admin); 6 nhóm RIASEC màu dịu cố định |
| Chữ | Inter cho mọi chữ giao diện; Playfair Display cho tiêu đề lớn trang khách (giống viger.edu.vn); JetBrains Mono cho SĐT, mã |
| Logo | `public/img/logo.png` của viger.edu.vn; admin dùng phần khiên trên nền navy |
| Nút | Chính (navy hoặc đỏ), phụ (viền), nguy hiểm (đỏ viền); cao ≥ 36px admin, ≥ 44px trang khách |
| Bảng | Tiêu đề xám nhạt, dòng 44px, tab bộ lọc, ô tìm, nút lọc dạng viên; điện thoại tự đổi thành thẻ |
| Trạng thái | Huy hiệu có chấm màu: xanh (tốt), vàng (cần chú ý), đỏ (lỗi/quá hạn), xám (đóng) |
| Hướng dẫn | Mỗi trang admin có dải "?" một câu giải thích "Trang này làm gì" |

### 7.3 Danh sách màn hình

**Holland – khách:** Giới thiệu · Làm bài (6 câu/trang) · Kết quả sơ lược · Thông tin + OTP · Báo cáo đầy đủ.

**Holland – /admin:** Đăng nhập · Tổng quan · Lượt làm bài · Thư viện câu hỏi · Cài đặt bài test · Danh mục (nhóm, cặp, 120 mã, ngành – nghề) · Trường thông tin khách · Nội dung trang · Kết nối CRM · Tài khoản, phân quyền · Nhật ký.

**CRM:** Đăng nhập · Tổng quan (đa kênh, phễu, hiệu suất) · Khách hàng · Hồ sơ khách 360 · Cần gọi · Lịch hẹn · Kênh và nguồn · Báo cáo · Xuất dữ liệu · Danh mục · Trường thông tin, giai đoạn, xếp loại · Tài khoản, phân quyền · Nhật ký truy cập · Cài đặt.

---

## 8. Bảo mật

- Cloudflare (WAF, chống bot, giới hạn tốc độ), Turnstile cho form công khai, Cloudflare Tunnel ẩn IP máy chủ.
- HTTPS, cookie `Secure`, `HttpOnly`, `SameSite=Lax`; CSRF; FormRequest cho mọi input; Blade `{{ }}`; Eloquent (không nối chuỗi SQL).
- Đăng nhập: khóa 15 phút sau 5 lần sai; mật khẩu ≥ 10 ký tự; buộc đổi mật khẩu tạm; (V2) xác thực 2 lớp cho quản trị viên.
- SĐT, email, họ tên **mã hóa** trong CSDL (`encrypted` cast) + cột hash để tìm; **không ghi vào log**.
- Token API lưu dạng hash, mỗi kênh một token, có scope, thu hồi được; so sánh `hash_equals`.
- `noindex, nofollow` cho mọi trang có dữ liệu khách và toàn bộ CRM; `robots.txt` chặn `/admin`.
- Sao lưu tự động hằng đêm ra ngoài máy chủ; thử khôi phục mỗi tháng; chặn `migrate:fresh/reset/db:wipe` khi production.
- Người dưới 18 tuổi cần phụ huynh xác nhận; chỉ thu thông tin tối thiểu; thời hạn lưu dữ liệu đặt trong cài đặt.

## 9. Tốc độ

- Blade + CSS/JS thuần, không SPA, không framework CSS nặng; mỗi trang chỉ nạp file của khu vực đó.
- Phân trang mọi danh sách; eager loading tránh N+1; chỉ mục cho cột lọc.
- Gửi CRM, gửi email, tính báo cáo chạy bằng **hàng đợi**; báo cáo tổng quan cache 5 phút.
- Ảnh nén WebP, lazy load; font tải bằng `display=swap`, chỉ các độ đậm cần dùng.

---

## 10. Giai đoạn xây dựng

Mỗi giai đoạn: báo kế hoạch → Vân duyệt → làm → test + pint → commit → cập nhật `TIEN_DO.md` → dừng.

### Holland (`feature/holland-v3`)

| GĐ | Việc | Xong khi |
|---|---|---|
| H1 | Dọn phần cowork đang dở (xem mục 11), hoàn thiện bảng dữ liệu, seeder 300 câu, danh mục, cài đặt, `form_fields.crm_key`, `roles/permissions`; trang Cài đặt bài test | `migrate --seed` chạy 2 lần không lỗi; test đạt |
| H2 | Module Assessment: rút đề, chấm, phân tích, gợi ý ngành | 8/8 ca của Thầy khớp; test ràng buộc rút đề |
| H3 | Giao diện khách theo mẫu v3: giới thiệu, làm bài 6 câu/trang, kết quả sơ lược | Làm trọn bài trên điện thoại 390px |
| H4 | Module Respondent: form thông tin, OTP email, đồng ý, báo cáo đầy đủ, email link | Test OTP, đồng ý dưới 18 tuổi |
| H5 | Module CrmSync + Tracking: outbox, job, thử lại, trang Kết nối CRM | Test `Http::fake()` (PROMPT_4) |
| H6 | Admin theo mẫu v3: lượt làm bài, thư viện câu hỏi, danh mục, trường thông tin, phân quyền, nhật ký | Test phân quyền từng vai trò |
| H7 | API cho chatbot, sao lưu, tài liệu, nghiệm thu | Bảng nghiệm thu đạt |

### CRM (`dev`)

| GĐ | Việc | Xong khi |
|---|---|---|
| C1 | Khởi tạo Laravel 13, hệ thống thiết kế, đăng nhập, `roles/permissions`, cấp tài khoản | Đăng nhập 3 vai trò, menu đúng quyền |
| C2 | Bảng dữ liệu mục 4.2, seeder danh mục (390 trường…), trường tùy biến, giai đoạn, tiêu chí xếp loại | `migrate --seed` 2 lần không lỗi |
| C3 | Module Intake + Channels: `POST /api/v1/leads` (`lead/2`), `/events`, khóa kênh, gộp trùng, idempotent, giao tự động | Test nhận từ 3 kênh, gửi lại không trùng |
| C4 | Khách hàng, hồ sơ 360, hành trình, cần gọi, lịch hẹn | Test phạm vi dữ liệu từng vai trò |
| C5 | Tổng quan, báo cáo, xuất dữ liệu (chỉ admin) + nhật ký | Tư vấn viên/nhân viên gọi URL xuất bị 403 |
| C6 | Kênh Facebook, Zalo (webhook), nhập Excel sự kiện | Test webhook giả |
| C7 | Bảo mật, sao lưu, giới hạn tốc độ xem hồ sơ, nhật ký truy cập | Kiểm thử bảo mật cơ bản đạt |
| C8 | Cài đặt, tài liệu, sổ tay, nghiệm thu | Thầy dùng thử được |

Thứ tự đề xuất: **H1 → H2 → C1 → C2 → C3 → H3 → H4 → H5 → C4 → C5 → H6 → C6 → C7 → H7 → C8.** Như vậy CRM nhận được khách thật ngay khi luồng khách Holland xong.

---

## 11. Việc cần làm ngay với repo Holland

1. Phần cowork làm ngày 12/11 **chưa commit**: 20 model, 5 migration, trang Cài đặt bài test, thêm vai trò COUNSELOR/STAFF. Test hiện đạt 41/41. Giữ lại làm nền cho H1, nhưng:
   - vai trò đổi thành bảng `roles/permissions` theo mục 6 (bỏ hằng số cứng trong `User.php` dần dần);
   - "Nhân viên văn phòng" đổi tên thành "Nhân viên".
2. Ba file tài liệu tiếng Việt bị đổi tên lỗi (`#U1ea4…`) khi nén: đã khôi phục tên gốc từ git, xóa bản lỗi.
3. `database/seeders/data/holland_v3/questions.json` (54 câu giả) đã xóa đúng ý, thay bằng `question_bank_300.csv`.
4. File `.env` có mật khẩu: **không gửi kèm** khi chia sẻ repo; đổi mật khẩu tài khoản `SPN` trước khi lên máy chủ thật.
