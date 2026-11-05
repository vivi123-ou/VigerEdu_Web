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

## Tiếp theo: Giai đoạn 3a
Form Phần A động → các trang câu hỏi Phần B (lưu tạm trong trình duyệt) → nộp bài (lưu attempt, chấm, đưa lead vào outbox) → trang kết quả (biểu đồ 6 nhóm, mã, ngành gợi ý, đặt lịch, Gọi/Zalo) → email kết quả.
