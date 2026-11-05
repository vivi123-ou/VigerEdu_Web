# Tiến độ

(Claude Code cập nhật sau mỗi phiên: đã xong · đang làm · câu hỏi cho Vân)

## Giai đoạn 1 – Khởi tạo (xong ngày 05/11/2026)

### Đã xong
- **Môi trường máy Vân:** Laragon 8.8 ở `D:\laragon` (PHP 8.3.36, Composer 2.10.3, MySQL 8.4.3, Mailpit). Đã bật extension `zip` (bản sao `php.ini.bak`), thêm PHP/Composer/MySQL vào PATH của tài khoản Windows.
- **Laravel 13.40** + **Laravel Boost** (dev). Đã gỡ 2 kỹ năng Boost không hợp: Tailwind, Laravel Cloud. Gỡ Vite/Tailwind/npm của bộ khung (dự án dùng CSS/JS thuần).
- **Git:** nhánh `dev`, remote `origin` = `github.com/vivi123-ou/VigerEdu_Holland` (**chưa push**).
- **Database MySQL `holland`** (utf8mb4). Bảng:
  - `users`: thêm `username`, `role` (ADMIN), `is_active`, `last_login_at`; email không bắt buộc.
  - `settings`: key/value có cache, helper `setting('khóa')`. 31 khóa nội dung mẫu (tên đơn vị, logo, màu, link web chính, chân trang, chữ trang giới thiệu, SEO, mã nhúng chatbot).
  - `question_sets`: nhiều bộ câu Phần B (thang trả lời, số câu/trang, bộ mặc định). Seed bộ `thpt` "THPT – 54 câu".
  - `questions`: Phần A + B, có `answer_type`, `options` (json), `is_required`, `show_if`, `lead_field`, `extra_field_key`, `riasec`, `max_score`, thùng rác (soft delete).
- **Seeder idempotent** (chỉ tạo cái chưa có, không ghi đè bản admin đã sửa, không tạo lại câu đã xóa):
  - **72 câu = 18 câu A + 54 câu B.** Tách 3 câu gộp theo thống nhất với Vân: A06 → A06 Họ tên PH + **A16** SĐT PH; A09 → A09 Tiếng Anh + **A17** Tiếng Đức; A11 → A11 Quốc gia + **A18** Khu vực.
  - `lead_field` theo tab 2 của từ điển; lựa chọn lấy giá trị chuẩn từ `lua_chon_chuan.json`. A11 mặc định chỉ có "Đức", admin tự thêm nước khác (giai đoạn 3).
  - A06, A16 chỉ hiện khi A01 = HS. Bắt buộc: A01, A15 (quy tắc "SĐT hoặc email" làm ở giai đoạn 3).
  - Tài khoản quản trị `SPN` tạo từ `.env` (không ghi đè mật khẩu khi chạy lại).
- **Đăng nhập admin** (`/admin/dang-nhap`): bằng tên đăng nhập hoặc email, "ghi nhớ đăng nhập", sai 5 lần thì khóa tạm, tài khoản bị khóa không vào được (kể cả đang trong phiên), thông báo tiếng Việt.
- **Layout admin theo H19:** thanh trên (logo, ô tìm lượt làm bài, "Xem trang test như khách", chuông thông báo, "Công cụ ▾", tài khoản), menu trái 4 nhóm Bài test / Nội dung trang / Dữ liệu / Cấu hình, đủ 15 mục; mục chưa làm hiện trang "Đang xây dựng – giai đoạn X". Nút **"?"** hướng dẫn ở mỗi trang. Trên điện thoại menu thu vào nút ☰.
- **Trang khách `/`** mobile-first: header (logo, tên, link "Về trang chủ"), trang giới thiệu (chữ lấy từ `settings`), chân trang, chỗ gắn chatbot `<div id="viger-chatbot">`. Nút "Bắt đầu làm bài" tạm khóa đến giai đoạn 3.
- **noindex** cho toàn bộ `/admin` (header `X-Robots-Tag` + thẻ meta).
- **Chặn `migrate:fresh` / `migrate:reset` / `db:wipe` khi production** (làm sớm từ giai đoạn 6).
- **Test:** 26 test / 179 kiểm tra đều đạt. Pint sạch; `pint.json` bỏ qua `database/seeders/data`.

### Chỉnh giao diện (theo góp ý của Vân)
- Font **Be Vietnam Pro** cho cả trang khách và admin (bỏ Inter + Playfair).
- Phong cách tham khảo web các đại học Đức: phẳng, góc vuông, căn trái, đường kẻ mảnh, nút chữ nhật "→", bảng thông tin nhanh; một màu nhấn (đỏ thương hiệu).
- Bỏ toàn bộ emoji, thay bằng icon SVG nét mảnh (`partials/icon.blade.php`). Thanh trên admin trên điện thoại chỉ còn icon.
- Đã kiểm tra bằng ảnh chụp ở khổ điện thoại 390px và máy tính 1366px.
- Phần bên phải trang giới thiệu đang trống: giai đoạn 3 thêm **ảnh banner** do admin tải lên.

### Lưu ý
- Lần chạy Pint đầu tiên đã định dạng lại `database/seeders/data/holland/holland_engine_reference.php` (chỉ dấu cách, xuống dòng; công thức không đổi, file vẫn chạy). Từ nay Pint bỏ qua thư mục này. Nếu Vân muốn giữ đúng bản gốc thì chép đè lại file từ gói tài liệu.
- Đã clone web chính vào `D:\OU\_ref\VigerEdu_Web` để tham khảo màu/font (chỉ đọc).
- `CLAUDE.md` mục 5 ghi "69 câu": đúng với file `questions.json`; sau khi tách câu, database có 72 câu.

### Còn lại của giai đoạn 1 (không chặn việc khác)
- Tour giới thiệu lần đăng nhập đầu và trang "Hướng dẫn sử dụng" theo vai trò: làm ở giai đoạn 3 khi đã có các trang thật.
- Ô "Tìm lượt làm bài" và chuông "lượt chưa gửi CRM" đang là chỗ chờ (giai đoạn 3, 4).

### Câu hỏi cho Vân
- Chưa có hotline, Zalo, địa chỉ, giờ làm việc để hiện ở chân trang (đang để trống, chân trang tự ẩn dòng trống). Vân gửi khi có, hoặc sửa trên admin từ giai đoạn 3.

### Chỉnh giao diện lần 2 (theo góp ý của Vân)
- Font giống viger.edu.vn: **Work Sans** (chữ) + **Playfair Display** (tiêu đề in hoa). Chọn được qua `settings.font_body` / `font_heading`, chỉ nhận font trong `config('holland.fonts')`.
- Trang giới thiệu tham khảo UEH "Hiểu về bạn": khối **Cách làm bài 3 bước** và **Phương pháp Holland – 6 nhóm** (chữ từ settings). Đổi "em" → "bạn".
- Admin: chữ 16px, nhãn "Sắp có", hướng dẫn nhanh trên bảng điều khiển.

## Giai đoạn 2 – Thuật toán chấm điểm (xong ngày 05/11/2026)

### Đã xong
- `App\Contracts\ScoringEngine` (`score()` từ bài làm, `scorePercent()` từ điểm % có sẵn) và `App\Contracts\MajorCatalog`.
- `App\Engines\RiasecFitEngineV2`: port nguyên công thức bản tham chiếu. Xếp hạng FIT → khớp mã → mã ngành; top 5 đại học + top 3 học nghề; `fields`, `testas`, `ranking` (toàn bộ ngành đang bật).
- `App\Data\HollandResult` (`toArray()` đúng `holland-result/1`, `toArray(withRanking: true)` cho `/score?full=1`), `MajorMatch`, `Major`, `ScoringConfig`.
- `App\Catalogs\JsonMajorCatalog`: đọc `majors.json`, ánh xạ khóa tiếng Việt (`id`, `loai`, `ten`, `linh_vuc`, `codes`…) sang khóa hợp đồng (`code`, `type`, `name_vi`, `field`, `holland_codes`…); nhận cả khóa của CRM.
- Chọn engine bằng `config('holland.engine')` + danh sách `config('holland.engines')`. Tham số chấm điểm mặc định ở `config('holland.scoring')` (khớp `holland_config.json`, có test kiểm tra).
- `docs/THAY_THUAT_TOAN.md`: cách viết và đăng ký engine mới.
- **Test (56 test / 69.501 kiểm tra, đều đạt):**
  - 5 học sinh `test_cases.json` (điểm %, mã, di3, consistency, has_tie, top 5 đại học với code_used/code_match/score_match/fit, top 3 học nghề, lĩnh vực mạnh nhất), sai số 0,001. HS01: AIS, N043, FIT 0,8011.
  - Đối chiếu với `holland_engine_reference.php` trên 300 hồ sơ ngẫu nhiên (thang Đúng/Không và 5 mức).
  - Bằng điểm, hồ sơ phẳng, ngành 3 mã, ngành tắt, thang Likert, nhãn, trọng số từ cấu hình, cấu trúc `holland-result/1`, engine giả thay qua cấu hình.

### Câu hỏi cho Vân
- Đã đổi câu lý do thành "AIS ↔ IAS · **bạn đạt** I 67%…" (Vân duyệt 05/11/2026), sửa cả mẫu trong `HOP_DONG_KET_NOI.md` và `CLAUDE.md`. **Cần báo bạn Ý (chatbot).**

## Giai đoạn 3a – Khách làm bài và xem kết quả (xong ngày 05/11/2026)

### Quyết định của Vân (khác đặc tả gốc)
- **Làm câu Holland trước, điền thông tin sau**: làm xong bài mới hiện form "Nhận kết quả của bạn", điền xong mới xem kết quả.
- **Bỏ nút đặt lịch** trên trang kết quả: tư vấn viên đặt lịch trong CRM rồi email cho khách. Trang kết quả có khối "Chuyên viên sẽ liên hệ" + Gọi / Zalo.
- Trang giới thiệu **không ghi số câu, không ghi thời gian**; lời mời viết kiểu marketing.
- Thang 5 mức hiển thị **5 ô số có nhãn** (tham khảo UEH), nhãn mặc định: Không đúng với tôi → Hoàn toàn đúng.
- Form thông tin: bắt buộc Họ tên, **Ngày sinh**, Email, SĐT, Bạn là, Đang học lớp/năm, **Trường**, **Tỉnh/Thành** + đồng ý. Thông tin phụ huynh / người giám hộ (họ tên, SĐT, **email**) không bắt buộc, chỉ hiện khi học sinh tự làm.

### Đã xong
- Bảng `attempts` (contact **mã hóa**), `outbox` (payload **mã hóa**). Model `Attempt`, `OutboxMessage`.
- Phần A mới: thêm A19 Ngày sinh, A20 Trường, A21 Tỉnh/Thành (34 tỉnh sau sắp xếp 2025 + "Ngoài Việt Nam"), A22 Email phụ huynh; A03 Năm sinh tắt (tự điền từ ngày sinh). Câu bắt buộc lên trước, câu không bắt buộc thu gọn.
- `/lam-bai` (`?bo=` chọn bộ câu): chia trang theo bộ, thanh tiến độ không ghi số, lưu tạm câu trả lời trên máy (không lưu thông tin cá nhân), câu điều kiện ẩn/hiện, chép utm_ từ link, không JS vẫn làm được.
- `POST /nop-bai` (5 lần/phút/IP): kiểm tra dựng động từ bảng câu hỏi (câu admin thêm tự được kiểm tra), chuẩn hóa SĐT, bắt buộc SĐT hoặc email → chấm → lưu → **gói `lead/1` vào outbox** (chờ gửi, giai đoạn 4 gửi thật) → email kết quả gửi sau khi trả trang.
- `/ket-qua/{token}` (noindex): lời chào, mã + giải thích 3 chữ, biểu đồ 6 nhóm (%), cảnh báo hồ sơ phẳng / bằng điểm, 3 lĩnh vực, 5 ngành đại học + 3 nghề (tên, tên tiếng Đức, mô tả, nhãn) — **không FIT số, không GREEN/YELLOW/RED**.
- Email kết quả (`TestResultMail`): chữ lấy từ settings. Máy Vân: `.env` gửi vào **Mailpit** (`http://localhost:8025`).
- Admin: **Lượt làm bài** (danh sách, lọc theo mã) + **chi tiết** (`view_url` gửi CRM): điểm 6 nhóm, xếp hạng đủ 75 ngành có FIT, lọc đại học / học nghề / lĩnh vực, bài làm từng câu, thông tin không nhạy cảm (SĐT/email/họ tên xem ở CRM). Bảng điều khiển đếm lượt hôm nay / tổng.
- Mọi chữ trên trang làm bài, form, trang kết quả, email nằm trong settings (nhóm `test`, `result`, `email`).
- **Test: 76 test đều đạt** (nộp hợp lệ → attempt + kết quả + outbox; gói đúng `lead/1`; câu admin thêm → `extra_fields`; thiếu đồng ý / thiếu SĐT+email → lỗi, không lưu; câu ẩn bị bỏ qua; bộ câu thang 5 mức chấm đúng; token sai 404; trang kết quả không có FIT/GREEN; email; SĐT không lộ trong DB/log; giới hạn 5 lần/phút; admin xem được, cần đăng nhập).
- Đã thử thật trên máy: nộp bài → kết quả AIS / N043 → email tới Mailpit.

### Việc Vân cần làm
- Thêm 2 dòng vào `TU_DIEN_DU_LIEU_CHUNG.xlsx` tab 2 (nhóm "Mở rộng"): `extra_fields.birth_date` (A19, ngày sinh, ngày) và `extra_fields.parent_email` (A22, email phụ huynh / người giám hộ). Thêm các mã câu A19–A22.
- Báo bạn Ý: câu lý do giờ là "bạn đạt…".

### Câu hỏi cho Vân
- Đặc tả ghi mặc định `MAIL_MAILER=log`, nhưng chế độ này **ghi cả địa chỉ email của khách vào file log**, trái quy tắc "không ghi SĐT/email vào log". Máy Vân mình đã chuyển sang Mailpit. Trên server thật cần một tài khoản gửi email (SMTP của tên miền hoặc Gmail/Google Workspace). Vân hỏi Thầy giúp email gửi đi là địa chỉ nào?

## Cập nhật dữ liệu 05/11/2026 (16 bang + Wien, 390 trường – Ý chốt)

Nguồn: `D:\OU\Cap_nhat_Holland_05-11.zip` (giống bản giải nén ở `D:\OU\Holland\docs\Cap_nhat_Holland_05-11\`).

### Đã làm
- Thay `docs/TU_DIEN_DU_LIEU_CHUNG.xlsx`, `docs/lua_chon_chuan.json` (thêm `school_type`, `school_ownership` cho CRM; danh sách cũ không đổi), thêm `database/seeders/data/holland/regions.json` (16 bang + Wien đang tắt).
- `docs/HOP_DONG_KET_NOI.md`: bản mới chỉ khác dòng mẫu câu lý do ("em đạt"); **giữ "bạn đạt"** theo quyết định của Vân → file không đổi.
- `questions.json` bản mới giống hệt bản cũ → không chép, không gộp gì.
- Câu A18 "Bang / khu vực mong muốn": đổi từ ô chữ sang **ô chọn 16 bang** lấy từ `regions.json` (chỉ khu vực đang bật). **Lưu và gửi CRM bằng tên bang** (ví dụ "Hessen") cho nhân viên dễ đọc, khớp hợp đồng `lead/1`. Seeder vẫn idempotent theo mã câu; DB máy Vân đã làm lại riêng A18.
- Mã trường (T + mã HRK 3 số): web Holland không dùng mã trường (ô "Trường đang học" là ô chữ) → không phải sửa.
- `CLAUDE.md` mục 5 ghi chú regions.json. Test 77 đạt.

### Chưa chép (không có trong danh sách cần thay)
- `3_Mau_GoogleSheet_Viger_Holland_v2.xlsx` bản mới (chỉ khác mã trường, tab CAU_HOI không đổi) và `DU_LIEU_16_BANG_390_TRUONG_DA_CHUAN_HOA.xlsx` (dữ liệu cho CRM).

### Dọn dẹp (Vân tự xóa khi không cần nữa)
- `D:\OU\Cap_nhat_Holland_05-11.zip`
- `D:\OU\Holland\docs\Cap_nhat_Holland_05-11\`

## Tiếp theo: Giai đoạn 3b – Admin quản lý câu hỏi
Sửa câu trên web (bảng, thêm, kéo thả, bật/tắt, xem như khách) · dán nhiều câu cùng lúc · nhập/xuất Excel đúng cột CAU_HOI (ô chọn sổ xuống, xem trước lỗi theo dòng) · nhân bản bộ câu · form Phần A (thêm câu, điều kiện hiện, "gửi sang CRM vào trường") · cấu hình chấm điểm có "Chạy thử".
