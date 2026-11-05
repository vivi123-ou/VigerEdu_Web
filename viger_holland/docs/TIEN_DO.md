# Tiến độ

(Claude Code cập nhật sau mỗi phiên: đã xong · đang làm · câu hỏi cho Vân)

## Tình trạng hiện tại (cập nhật 05/11/2026)

| Giai đoạn | Trạng thái |
|---|---|
| 1. Khởi tạo, layout, đăng nhập admin, seeder | ✅ Xong |
| 2. Thuật toán chấm điểm `RiasecFitEngineV2` | ✅ Xong |
| 3a. Khách làm bài → điền thông tin → xem kết quả, email, lượt làm bài trong admin | ✅ Xong |
| Cập nhật dữ liệu 05/11 (16 bang + Wien, từ điển mới) | ✅ Xong |
| 3b. Admin quản lý câu hỏi, Excel, form Phần A, cấu hình chấm điểm | ⏳ Đã gửi kế hoạch, **chờ Vân đồng ý** |
| 3c. Admin nội dung trang (banner, chữ, email, chân trang), khối nhúng | Chưa làm |
| 4. Kết nối CRM (gửi thật, thử lại, danh mục ngành từ CRM) | Chưa làm |
| 5–7. API v1, an toàn dữ liệu, Excel kết quả, Cài đặt, SEO, `/install`, tài liệu | Chưa làm |

**Con số hiện tại:** 76 câu hỏi (22 câu Phần A, 21 đang bật + 54 câu Phần B mẫu) · 1 bộ câu `thpt` · 75 ngành/nghề · **77 test đều đạt** · nhánh `dev`, **chưa push**.

**Chạy trên máy Vân:** mở `D:\laragon\laragon.exe` → **Start All** → trong thư mục dự án gõ `php artisan serve`.
- Trang khách: http://127.0.0.1:8000 · Admin: http://127.0.0.1:8000/admin (tài khoản `SPN`)
- Email thử (Mailpit): http://localhost:8025

### Việc Vân cần làm
1. Thêm vào `TU_DIEN_DU_LIEU_CHUNG.xlsx` tab 2 (nhóm "Mở rộng"): `extra_fields.birth_date` (câu A19, ngày sinh) và `extra_fields.parent_email` (câu A22, email phụ huynh / người giám hộ); ghi thêm mã câu A19–A22. (Bản từ điển mới 05/11 chưa có 2 dòng này.)
2. Báo bạn Ý (chatbot): câu lý do kết quả ngành là **"bạn đạt…"** (bản hợp đồng Ý gửi 05/11 vẫn ghi "em đạt").
3. Xóa gói cập nhật khi không cần nữa: `D:\OU\Cap_nhat_Holland_05-11.zip` và thư mục `D:\OU\Holland\docs\Cap_nhat_Holland_05-11\`.
4. Khi đưa lên server thật: **đổi mật khẩu** tài khoản `SPN` (mật khẩu hiện tại đã gõ trong khung chat).

### Câu hỏi cho Vân / Thầy
1. **Email gửi đi** khi chạy thật là địa chỉ nào (ví dụ `tuvan@viger.edu.vn`) và thông tin SMTP? Đặc tả ghi `MAIL_MAILER=log` nhưng chế độ đó ghi email khách vào file log, trái quy tắc bảo mật → máy Vân đang dùng Mailpit.
2. **Hotline, Zalo, địa chỉ, giờ làm việc** để hiện ở chân trang và nút Gọi / Zalo trên trang kết quả (đang để trống, các ô trống tự ẩn).
3. Có chép thêm vào `docs/` sheet mẫu mới (`3_Mau_GoogleSheet_Viger_Holland_v2.xlsx`, chỉ khác mã trường) và file `DU_LIEU_16_BANG_390_TRUONG_DA_CHUAN_HOA.xlsx` không?
4. Ảnh banner cho trang giới thiệu (phần bên phải đang trống trên máy tính) – nếu có ảnh sẵn.

### Các quyết định đã chốt (khác hoặc bổ sung so với đặc tả gốc)
| Ngày | Quyết định |
|---|---|
| 05/11 | Tách câu gộp: A06 → A06 + **A16** (SĐT phụ huynh); A09 → A09 + **A17** (tiếng Đức); A11 → A11 + **A18** (khu vực) |
| 05/11 | Vai trò tài khoản: hiện chỉ `ADMIN`; vai trò marketing (`EDITOR`) làm ở giai đoạn 3 |
| 05/11 | Database máy Vân: MySQL của Laragon, tên `holland` |
| 05/11 | Giao diện: font **giống viger.edu.vn (Work Sans + Playfair Display)**, phong cách phẳng như web đại học Đức, không emoji |
| 05/11 | Xưng hô **"bạn"** (đối tượng gồm cả sinh viên), cả câu lý do: "AIS ↔ IAS · **bạn đạt** I 67%…" |
| 05/11 | Trang giới thiệu **không ghi số câu, không ghi thời gian**, lời mời kiểu marketing |
| 05/11 | **Làm câu Holland trước, điền thông tin sau** – điền xong mới xem kết quả |
| 05/11 | **Không có nút đặt lịch** – tư vấn viên đặt lịch trong CRM rồi email cho khách |
| 05/11 | Thang 5 mức: **5 ô số có nhãn** (tham khảo UEH), không dùng ngôi sao |
| 05/11 | Form thông tin bắt buộc: Họ tên, **Ngày sinh**, Email, SĐT, Bạn là, Lớp/năm, **Trường**, **Tỉnh/Thành** + đồng ý; phụ huynh / người giám hộ (họ tên, SĐT, **email**) không bắt buộc |
| 05/11 | Ngày sinh và email phụ huynh gửi CRM trong `extra_fields` (không đổi hợp đồng `lead/1`) |
| 05/11 | Khu vực (A18): ô chọn 16 bang từ `regions.json`, **gửi CRM bằng tên bang** ("Hessen") cho nhân viên dễ đọc |
| 05/11 | Nhập câu hỏi (3b): sửa trên web, dán nhiều câu, Excel, nhân bản bộ câu (Google Sheets để sau) |

### Lịch sử commit (nhánh `dev`)
| Commit | Nội dung |
|---|---|
| `9856c54` | Giai đoạn 1: khởi tạo Laravel 13, Boost, layout, đăng nhập admin, seeder |
| `f7c81d2`, `b362908` | Giao diện phẳng, bỏ emoji |
| `fd58e48` | Xưng hô "em" → "bạn" ở trang giới thiệu |
| `d33ac23` | Font giống viger.edu.vn, khối Phương pháp Holland + Cách làm bài, admin dễ đọc |
| `d556444` | Giai đoạn 2: thuật toán chấm điểm |
| `aac8f0a` | Câu lý do "bạn đạt" |
| `ddc63aa` | Trang giới thiệu viết lại kiểu marketing, bỏ số câu và thời gian |
| `60917ce` | Giai đoạn 3a: làm bài, thông tin, kết quả, email, lượt làm bài |
| `329240e` | Cập nhật dữ liệu 05/11 (regions.json, từ điển mới) |

---

## Giai đoạn 1 – Khởi tạo (xong 05/11/2026)

- **Môi trường máy Vân:** Laragon 8.8 ở `D:\laragon` (PHP 8.3.36, Composer 2.10.3, MySQL 8.4.3, Mailpit). Đã bật extension `zip` (bản sao `php.ini.bak`), thêm PHP / Composer / MySQL vào PATH của tài khoản Windows. (Có một bản Laragon cài thiếu ở `D:\Program Files\laragon` – không dùng, có thể gỡ.)
- **Laravel 13.40** + **Laravel Boost** (dev). Gỡ 2 kỹ năng Boost không hợp (Tailwind, Laravel Cloud) và Vite/Tailwind/npm của bộ khung (dự án dùng CSS/JS thuần).
- **Git:** nhánh `dev`, remote `origin` = `github.com/vivi123-ou/VigerEdu_Holland` (chưa push). `.env` (có mật khẩu) không vào git.
- **Bảng:** `users` (thêm `username`, `role`, `is_active`, `last_login_at`), `settings` (key/value có cache, helper `setting('khóa')`), `question_sets` (nhiều bộ câu Phần B), `questions` (Phần A + B, thùng rác).
- **Seeder idempotent:** chỉ tạo cái chưa có, không ghi đè bản admin đã sửa, không tạo lại câu đã xóa. Tài khoản quản trị `SPN` tạo từ `.env`.
- **Đăng nhập admin** (`/admin/dang-nhap`): tên đăng nhập hoặc email, ghi nhớ, sai 5 lần khóa tạm, tài khoản bị khóa không vào được, thông báo tiếng Việt.
- **Layout admin theo H19:** thanh trên (logo, tìm lượt làm bài, "Xem trang test như khách", thông báo, Công cụ, tài khoản), menu trái 4 nhóm đủ 15 mục, mục chưa làm có nhãn "Sắp có", nút **"?"** hướng dẫn mỗi trang, hướng dẫn nhanh trên bảng điều khiển. Dùng được trên điện thoại.
- **noindex** cho toàn bộ `/admin`. **Chặn `migrate:fresh` / `reset` / `db:wipe` khi production.**
- **Giao diện** (sau 2 lần chỉnh theo góp ý): font Work Sans + Playfair Display (chọn qua `settings.font_body` / `font_heading`, chỉ nhận font trong `config('holland.fonts')`), phẳng, góc vuông, icon SVG (`partials/icon.blade.php`), kiểm tra bằng ảnh chụp ở 390px và 1366px.
- **Trang giới thiệu:** tiêu đề "Bạn thật sự hợp với ngành nào?", 3 lợi ích, nút "Bắt đầu khám phá", khối Cách làm bài 3 bước, khối Phương pháp Holland – 6 nhóm (tham khảo UEH "Hiểu về bạn"). Mọi chữ lấy từ `settings`.

**Lưu ý:**
- Lần chạy Pint đầu tiên đã định dạng lại `holland_engine_reference.php` (chỉ dấu cách, xuống dòng; công thức không đổi, file vẫn chạy, test đối chiếu vẫn đạt). Từ nay `pint.json` bỏ qua `database/seeders/data`.
- Web chính được clone vào `D:\OU\_ref\VigerEdu_Web` để tham khảo (chỉ đọc).

**Còn lại:** tour giới thiệu lần đăng nhập đầu và trang "Hướng dẫn sử dụng" theo vai trò (làm khi đã có đủ trang admin); ô "Tìm lượt làm bài" và chuông "lượt chưa gửi CRM" đang là chỗ chờ.

## Giai đoạn 2 – Thuật toán chấm điểm (xong 05/11/2026)

- `App\Contracts\ScoringEngine` (`score()` từ bài làm, `scorePercent()` từ điểm % có sẵn), `App\Contracts\MajorCatalog`.
- `App\Engines\RiasecFitEngineV2`: port nguyên công thức bản tham chiếu. Xếp hạng FIT → khớp mã → mã ngành; top 5 đại học + top 3 học nghề; `fields`, `testas`, `ranking` (toàn bộ ngành đang bật).
- `App\Data\HollandResult` (`toArray()` đúng `holland-result/1`, `toArray(withRanking: true)` cho `/score?full=1`), `MajorMatch`, `Major`, `ScoringConfig`.
- `App\Catalogs\JsonMajorCatalog`: đọc `majors.json`, ánh xạ khóa tiếng Việt sang khóa hợp đồng; nhận cả khóa của CRM.
- Chọn engine bằng `config('holland.engine')` + `config('holland.engines')`; tham số mặc định ở `config('holland.scoring')` (khớp `holland_config.json`).
- `docs/THAY_THUAT_TOAN.md`: cách viết và đăng ký engine mới.
- **Kiểm tra:** 5 học sinh `test_cases.json` khớp (sai số 0,001; HS01: AIS, N043, FIT 0,8011); đối chiếu với file tham chiếu trên 300 hồ sơ ngẫu nhiên; bằng điểm, hồ sơ phẳng, ngành 3 mã, ngành tắt, thang Likert, cấu trúc kết quả, engine giả thay qua cấu hình.

## Giai đoạn 3a – Khách làm bài và xem kết quả (xong 05/11/2026)

- Bảng `attempts` (thông tin liên hệ **mã hóa**) và `outbox` (gói gửi CRM **mã hóa**).
- **Phần A (form "Nhận kết quả của bạn"):** thêm A19 Ngày sinh, A20 Trường đang học, A21 Tỉnh/Thành (34 tỉnh sau sắp xếp 2025 + "Ngoài Việt Nam"), A22 Email phụ huynh; A03 Năm sinh tắt (tự điền từ ngày sinh). Câu bắt buộc hiện trước, câu không bắt buộc thu gọn trong "Thêm thông tin…"; thông tin phụ huynh chỉ hiện khi học sinh tự làm.
- **`/lam-bai`** (`?bo=` chọn bộ câu): chia trang theo bộ, thanh tiến độ không ghi số, thang 5 mức có bảng "Cách chọn", lưu tạm câu trả lời trên máy (không lưu thông tin cá nhân), câu điều kiện ẩn/hiện, chép tham số `utm_` từ link, tắt JS vẫn làm được.
- **`POST /nop-bai`** (5 lần/phút/IP): kiểm tra dựng tự động từ bảng câu hỏi (câu admin thêm tự được kiểm tra), chuẩn hóa SĐT, phải có SĐT hoặc email → chấm điểm → lưu → **gói `lead/1` + `holland_result` + `quiz` vào hàng đợi CRM** (trạng thái "Chờ gửi"; giai đoạn 4 mới gửi thật) → email kết quả gửi sau khi đã trả trang cho khách.
- **`/ket-qua/{mã}`** (noindex): lời chào, mã Holland + giải thích 3 chữ, biểu đồ 6 nhóm (%), câu nhắc khi hồ sơ phẳng / bằng điểm, 3 lĩnh vực, 5 ngành đại học + 3 nghề (tên, tên tiếng Đức, mô tả, nhãn), khối "Chuyên viên sẽ liên hệ" + Gọi / Zalo. **Không có điểm FIT, không có GREEN/YELLOW/RED, không hứa đậu.**
- **Email kết quả** (`TestResultMail`): mã Holland, link xem lại, lời hẹn tư vấn; chữ lấy từ settings.
- **Admin – Lượt làm bài:** danh sách (lọc theo mã) và chi tiết (chính là `view_url` gửi CRM): điểm 6 nhóm, xếp hạng đủ 75 ngành có FIT, lọc đại học / học nghề / lĩnh vực, bài làm từng câu. Không hiện họ tên, SĐT, email (xem ở CRM). Bảng điều khiển đếm lượt hôm nay / tổng.
- Mọi chữ trên trang làm bài, form, trang kết quả, email nằm trong settings (nhóm `test`, `result`, `email`).
- **Kiểm tra:** nộp hợp lệ → có lượt làm bài + kết quả + gói chờ gửi; gói đúng `lead/1`; câu admin thêm → `extra_fields`; thiếu đồng ý hoặc thiếu cả SĐT lẫn email → báo lỗi, không lưu; câu ẩn bị bỏ qua; bộ câu thang 5 mức chấm đúng; mã kết quả sai → 404; email; SĐT không lộ trong database / log; giới hạn 5 lần/phút; admin cần đăng nhập. Đã thử thật trên máy: kết quả AIS / N043, email tới Mailpit.

## Cập nhật dữ liệu 05/11/2026 (16 bang + Wien, 390 trường – Ý chốt)

Nguồn: `D:\OU\Cap_nhat_Holland_05-11.zip` (giống bản giải nén ở `D:\OU\Holland\docs\Cap_nhat_Holland_05-11\`; thư mục `docs/_cap_nhat_05-11/` không tồn tại).

- Thay `docs/TU_DIEN_DU_LIEU_CHUNG.xlsx` (mã trường = T + mã HRK 3 số, thêm loại trường) và `docs/lua_chon_chuan.json` (thêm `school_type`, `school_ownership` cho CRM; danh sách cũ không đổi).
- Thêm `database/seeders/data/holland/regions.json` (16 bang + Wien đang tắt) – dữ liệu thật, ghi chú trong `CLAUDE.md` mục 5.
- `docs/HOP_DONG_KET_NOI.md`: bản mới chỉ khác dòng mẫu câu lý do ("em đạt") → giữ "bạn đạt", file không đổi.
- `questions.json` bản mới giống hệt bản cũ → giữ nguyên.
- Câu A18 thành **ô chọn 16 bang** (chỉ khu vực đang bật), gửi CRM bằng tên bang. Database máy Vân đã làm lại riêng câu A18 (lượt làm bài cũ vẫn còn).
- Mã trường: web Holland không dùng (ô "Trường đang học" là ô chữ) → không phải sửa.

## Tiếp theo: Giai đoạn 3b – Admin quản lý câu hỏi (chờ Vân đồng ý)

1. **Bộ câu hỏi (Phần B):** thêm bộ, nhân bản bộ, bật/tắt, kiểu trả lời (Đúng/Không, 3 hoặc 5 mức) và nhãn từng mức, số câu mỗi trang; trong bộ: sửa chữ, chọn nhóm "R – Kỹ thuật…", bật/tắt, kéo thả thứ tự, thùng rác, "Xem như khách".
2. **Dán nhiều câu cùng lúc** (mỗi dòng một câu, mã tự đặt).
3. **Excel** (`openspout`): tải file đúng cột tab `CAU_HOI` có ô chọn sổ xuống; nhập lại có xem trước, báo lỗi theo dòng, khớp theo mã câu, câu thiếu chỉ bị tắt, lưu lịch sử nhập.
4. **Form thông tin (Phần A):** thêm/sửa câu, kiểu, lựa chọn, bắt buộc, điều kiện hiện, "Gửi sang CRM vào trường…" (trường chuẩn hoặc mã tùy biến → `extra_fields`).
5. **Cấu hình chấm điểm:** trọng số, ngưỡng và tên nhãn, số ngành, ngưỡng hồ sơ phẳng, nút "Chạy thử".
6. **Mô tả 6 nhóm** R I A S E C.
7. **Test chấp nhận của đặc tả:** thêm câu "Trường chuyên?" (`truong_chuyen`) + bộ 30 câu thang 5 mức trên admin → khách thấy, chấm đúng, CRM nhận `extra_fields.truong_chuyen` – không sửa code.
