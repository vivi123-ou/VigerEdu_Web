# Tiến độ

(Claude Code cập nhật sau mỗi phiên: đã xong · đang làm · câu hỏi cho Vân)

## Tình trạng hiện tại (cập nhật 05/11/2026)

| Giai đoạn | Trạng thái |
|---|---|
| 1. Khởi tạo, layout, đăng nhập admin, seeder | ✅ Xong |
| 2. Thuật toán chấm điểm `RiasecFitEngineV2` | ✅ Xong |
| 3a. Khách làm bài → điền thông tin → xem kết quả, email, lượt làm bài trong admin | ✅ Xong |
| Cập nhật dữ liệu 05/11 (16 bang + Wien, từ điển mới) | ✅ Xong |
| 3b. Admin quản lý câu hỏi, Excel, form Phần A, cấu hình chấm điểm | ✅ Xong (chưa thử bằng trình duyệt trên máy Vân) |
| 3c. Nội dung trang (banner, khối, kết quả, email, giao diện, chân trang), thư viện ảnh, khối nhúng, vai trò marketing | ✅ Xong |
| 4. Kết nối CRM (gửi thật, thử lại, danh mục ngành từ CRM) | Chưa làm |
| 5–7. API v1, an toàn dữ liệu, Excel kết quả, Cài đặt, SEO, `/install`, tài liệu | Chưa làm |

**Con số hiện tại:** 76 câu hỏi (22 câu thông tin khách, 12 đang bật + 54 câu Holland mẫu) · 1 bộ câu `thpt` · 75 ngành/nghề · **146 test đều đạt** · nhánh `dev`, **chưa push**.

**Chạy trên máy Vân:** mở `D:\laragon\laragon.exe` → **Start All** → trong thư mục dự án gõ `php artisan serve`.
- Trang khách: http://127.0.0.1:8000 · Admin: http://127.0.0.1:8000/admin (tài khoản `SPN`)
- Email thử (Mailpit): http://localhost:8025

### Việc Vân cần làm
1. Thêm vào `TU_DIEN_DU_LIEU_CHUNG.xlsx` tab 2 (nhóm "Mở rộng"): `extra_fields.birth_date` (câu A19, ngày sinh) và `extra_fields.parent_email` (câu A22, email phụ huynh / người giám hộ); ghi thêm mã câu A19–A22. (Bản từ điển mới 05/11 chưa có 2 dòng này.)
2. Báo bạn Ý (chatbot): câu lý do kết quả ngành là **"bạn đạt…"** (bản hợp đồng Ý gửi 05/11 vẫn ghi "em đạt").
3. Xóa gói cập nhật khi không cần nữa: `D:\OU\Cap_nhat_Holland_05-11.zip` và thư mục `D:\OU\Holland\docs\Cap_nhat_Holland_05-11\`.
4. Khi đưa lên server thật: **đổi mật khẩu** tài khoản `SPN` (mật khẩu hiện tại đã gõ trong khung chat).
5. Mỗi lần lấy code mới: bật Laragon (Start All) rồi chạy `php artisan migrate` và `php artisan db:seed --class=SettingSeeder` (seeder chỉ thêm ô mới, không ghi đè chữ đã sửa). Lần này có thêm bảng `media` (thư viện ảnh) và các ô nội dung mới. Máy Vân đã chạy `php artisan storage:link`.
6. Muốn thử vai trò marketing: thêm `HOLLAND_EDITOR_USERNAME` + `HOLLAND_EDITOR_PASSWORD` vào `.env` rồi chạy `php artisan db:seed --class=AdminSeeder`.

### Câu hỏi cho Vân / Thầy
1. **Email gửi đi** khi chạy thật là địa chỉ nào (ví dụ `tuvan@viger.edu.vn`) và thông tin SMTP? Đặc tả ghi `MAIL_MAILER=log` nhưng chế độ đó ghi email khách vào file log, trái quy tắc bảo mật → máy Vân đang dùng Mailpit.
2. **Hotline, Zalo, địa chỉ, giờ làm việc** để hiện ở chân trang và nút Gọi / Zalo trên trang kết quả (đang để trống, các ô trống tự ẩn).
3. Có chép thêm vào `docs/` sheet mẫu mới (`3_Mau_GoogleSheet_Viger_Holland_v2.xlsx`, chỉ khác mã trường) và file `DU_LIEU_16_BANG_390_TRUONG_DA_CHUAN_HOA.xlsx` không?
4. Ảnh banner cho trang giới thiệu (phần bên phải đang trống trên máy tính) – nếu có ảnh sẵn.
5. **Excel câu hỏi – cột "Bắt buộc trước khi chuyển tư vấn"**: đang dùng làm "Bắt buộc trả lời" của câu Phần A trên form. Đúng ý Thầy không, hay cột này chỉ dành cho chatbot (khi đó form cần cột riêng)?
6. **Excel câu hỏi – cột "Ghi vào cột"**: file mẫu ghi kiểu `DS_KHACH.Vai trò`; web Holland ghi mã trường CRM (`role`, `extra_fields.truong_chuyen`) vì đó là chỗ dữ liệu thật sự được gửi. Giữ như vậy được không? Các cột "Loại", "Dùng chấm xếp loại?", "AI cần thu" được giữ cho giống mẫu nhưng web không dùng.
7. **Chọn bộ câu theo câu trả lời Phần A** (đặc tả mục 0b.2): vì đã đổi thứ tự (làm câu Holland trước, điền thông tin sau) nên không làm được; hiện chọn bộ bằng link `?bo=mã`. Đồng ý không?

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
| 05/11 | Vân đồng ý kế hoạch 3b ("tiếp tục giai đoạn đang làm") |

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

## Giai đoạn 3b – Admin quản lý câu hỏi (xong 05/11/2026)

Menu trái: 5 mục bỏ nhãn "Sắp có" – Bộ câu hỏi, Form thông tin khách, Cấu hình chấm điểm, Mô tả 6 nhóm Holland, Xuất / nhập Excel. Mỗi trang có nút "?" hướng dẫn.

- **Bộ câu hỏi (Phần B)** `/admin/question-sets`: thêm bộ (mã tự tạo từ tên), sửa tên / mã / kiểu trả lời (Đúng-Không, 3 mức, 5 mức) / **nhãn từng mức** / số câu mỗi trang / bật-tắt / bộ mặc định; **nhân bản** (bộ mới đang tắt, mã câu mới); xóa vào **thùng rác** + khôi phục; không tắt / xóa được bộ mặc định. Mỗi bộ có **link làm bài + nút "Chép link"**, đếm câu theo nhóm R I A S E C.
  - Đổi kiểu trả lời: nhãn đặt lại như mặc định, điểm tối đa các câu theo thang mới (chấm theo % nên vẫn đúng).
- **Câu Holland**: thêm (mã tự đặt B55, B56…; nút "Lưu và thêm câu khác"), sửa (chữ, nhóm R–C có tên nhóm, tùy chọn nâng cao: thang riêng, điểm tối đa, thứ tự), bật/tắt một bấm, **kéo thả ⋮⋮ + "Lưu thứ tự"**, thùng rác.
  - **Dán nhiều câu**: mỗi dòng một câu, đầu dòng "R | …" hoặc chọn nhóm chung; dòng thiếu nhóm báo đúng số dòng, có lỗi thì không thêm câu nào.
- **"Xem như khách"**: admin đang đăng nhập mở `/lam-bai?bo=…&xem_truoc=1`, xem được cả bộ đang tắt, có dòng báo "Đang xem trước" (khách không thấy).
- **Form thông tin (Phần A)** `/admin/info-form`: thêm / sửa câu (9 kiểu), lựa chọn mỗi dòng một ("Chữ hiển thị = mã"), chữ gợi ý, chú thích, bắt buộc, **"Chỉ hiện khi…"** (chọn câu + câu trả lời), **"Gửi sang CRM vào trường…"** (20 trường chuẩn `lead/1` có tên tiếng Việt, hoặc "Trường mới" → `extra_fields.{mã}`, mã tự chuyển không dấu). Chặn 2 câu cùng gửi một trường. Ô đồng ý không tắt / xóa được, chỉ sửa chữ. Câu mới tự đứng trước ô đồng ý.
- **Cấu hình chấm điểm** `/admin/scoring`: nhập theo % (trọng số khớp mã / khớp điểm phải cộng 100; ngưỡng + tên nhãn, tối đa 6 dòng, cần 1 nhãn 0%; số ngành đại học / nghề; ngưỡng hồ sơ phẳng; cách xưng hô câu lý do). **"Chạy thử"**: chọn học sinh mẫu (5 em trong test_cases.json) hoặc tự nhập % 6 nhóm → so sánh cạnh nhau "cấu hình đang dùng" và "với các con số vừa gõ", **không lưu**. "Đặt lại như ban đầu". Lưu trong `settings.scoring_config`; engine, trang kết quả, trang lượt làm bài đều đọc từ đây.
- **Mô tả 6 nhóm** `/admin/groups`: tên + giải thích R I A S E C (trang kết quả, email).
- **Excel** `/admin/excel`: "Tải bộ câu hỏi" theo bộ → tab `CAU_HOI` **đúng 13 cột, đúng thứ tự** của sheet mẫu (Phần A + Phần B của bộ) + tab `HUONG_DAN`; **ô chọn sổ xuống** cho Phần, Kiểu trả lời, Nhóm, is_active, Bắt buộc (openspout không có tính năng này → tự chèn vào file sau khi ghi, không thêm package). "Nhập bộ câu hỏi" → **xem trước** (số câu thêm / sửa / tắt / dòng lỗi; từng dòng: lỗi đỏ hoặc mục thay đổi) → "Xác nhận nhập". Khớp theo mã câu, ô mã trống = câu mới, câu thiếu trong file **chỉ bị tắt**, ô đồng ý không bao giờ bị tắt, còn một dòng lỗi là không ghi gì. Lịch sử nhập hiện trên trang.
- **Nhật ký + quay lại bản cũ**: bảng mới `admin_activities` (ai, lúc nào, sửa gì, bản trước). Trang sửa câu, bộ câu, cấu hình chấm điểm, mô tả nhóm có khối "Lịch sử thay đổi" + nút **"Quay lại bản này"**. Thao tác lớn (xóa, nhân bản, lưu cấu hình, nhập Excel) hỏi xác nhận.
- **Test (38 test mới):** đủ các việc trên, gồm **test chấp nhận bắt buộc** của đặc tả mục 0b.5 (`OpenAdminAcceptanceTest`: chỉ dùng trang admin thêm câu "Trường chuyên?" → `truong_chuyen` + bộ 30 câu thang 5 mức → khách thấy câu mới, mã AIS chấm đúng thang 0–4, gói CRM có `extra_fields.truong_chuyen`); Excel theo yêu cầu giai đoạn 7 (file xuất đúng tab + cột như mẫu; file có dòng sai → báo đúng dòng, không ghi; file đúng → cập nhật, không xóa câu thiếu; file xuất nhập lại không đổi gì).

**Chưa làm / để sau:** vai trò marketing (`EDITOR`) – làm cùng 3c; Google Sheets; xuất Excel kết quả làm bài (giai đoạn 7); chưa chụp ảnh màn hình kiểm tra giao diện các trang mới (máy chưa bật MySQL) – Vân mở thử giúp.

### Bảng nghiệm thu – việc đã làm được đến giai đoạn 3b
| Vai trò | # | Việc | Trạng thái |
|---|---|---|---|
| Sếp | 4 | Sửa 1 câu hỏi Holland, thêm 1 câu Phần A "Trường chuyên?" → khách thấy câu mới, CRM nhận câu trả lời | ✅ Thử được (CRM: gói nằm trong hàng đợi, giai đoạn 4 mới gửi thật) |
| Sếp | 5 | Đổi chữ trên trang kết quả Holland | Một phần: tên + mô tả 6 nhóm (3b); các chữ khác ở 3c |

## Bổ sung 05/11/2026 – Câu hỏi kiểu Google Form, form gọn hơn, văn phong

- **Form "Nhận kết quả":** bỏ khối thu gọn "Thêm thông tin…"; câu hiện theo thứ tự admin xếp. 9 câu nhu cầu du học (GPA, ngoại ngữ, ngân sách, thời điểm…) **tắt sẵn** – chatbot / tư vấn viên hỏi; bật lại trên admin được.
- **Câu Holland kiểu Google Form:** mỗi câu chọn kiểu riêng – Đúng / Không, Thang 3 / 5 mức, **Thang mức tự đặt**, **Chọn 1**, **Chọn nhiều** (mỗi lựa chọn gắn nhóm + điểm, giới hạn số ô); hiện dạng **ô số hoặc ngôi sao**, chọn 1 dạng nút hoặc danh sách sổ xuống; **câu không bắt buộc**; bộ câu có **xáo trộn thứ tự câu** và kiểu hiện mặc định.
  - Chấm điểm: mọi kiểu quy về "điểm đạt / tối đa từng nhóm" (`AnswerScorer`) rồi dùng **đúng công thức cũ**. Câu không bắt buộc bỏ trống thì không tính.
  - Gói CRM: `quiz.answers` **thêm** `type`, `answer`, `scores` (không đổi trường cũ) – đã ghi vào `HOP_DONG_KET_NOI.md`.
  - Excel: câu chọn ghi `Sửa xe = R:1 | Vẽ tranh = A:2`; cột "Bắt buộc…" dùng cho cả câu Holland.
- **Form thông tin khách:** thêm kiểu Đoạn văn, Đánh giá (ngôi sao); lựa chọn "Khác (ghi rõ)"; số nhỏ nhất / lớn nhất do admin đặt (bỏ ghi cứng GPA 0–10); ô **"Thông tin riêng tư"** (SĐT, email luôn ẩn) thay cho danh sách ẩn ghi cứng ở trang Lượt làm bài.
- **Văn phong:** viết lại chữ trang giới thiệu, làm bài, kết quả, email, SEO, chân trang theo giọng đơn vị tư vấn du học (rõ, lịch sự, không sáo rỗng, bỏ câu cảm thán và câu hỏi tu từ); **bỏ "miễn học phí"** ở chân trang (không đúng ở mọi bang, đặc tả yêu cầu không hứa). Database máy Vân đã cập nhật (chỉ thay chữ còn là bản mặc định).
- Migration chỉ thêm cột: `questions.display`, `questions.config`, `question_sets.display`. **128 test đều đạt.**

**Việc Vân cần làm:** báo bạn Ý về 3 trường mới trong `quiz.answers` (xem `HOP_DONG_KET_NOI.md`).

## Giai đoạn 3c – Nội dung trang (xong 06/11/2026)

Mọi chữ, ảnh, màu khách thấy sửa được trên admin. Mỗi trang có **"Xem trước"** (mở trang khách ở tab mới với nội dung chưa lưu), **Lưu**, **Lịch sử thay đổi + "Quay lại bản này"**.

- **Khung trang nội dung dùng chung** (`config/admin_content.php`, `ContentController`): mỗi trang là danh sách ô (chữ, đoạn văn, chữ có định dạng, ảnh, màu, font, địa chỉ web, danh sách link, tên miền). Thêm ô mới = thêm một dòng config.
  - **Trang giới thiệu – tab Banner:** dòng chữ nhỏ, tiêu đề, đoạn giới thiệu, 3 lợi ích, chữ nút, **ảnh banner máy tính + điện thoại**.
  - **Trang kết quả:** lời chào, mở đầu, tiêu đề các phần, câu nhắc hồ sơ phẳng / bằng điểm, khối liên hệ (chữ có định dạng), lưu ý. "Xem trước" dùng kết quả học sinh mẫu HS01 (không lưu).
  - **Email gửi khách:** tiêu đề, lời chào, mở đầu, lời kết (có định dạng), chữ ký. "Xem trước" hiện email mẫu.
  - **Giao diện & chân trang:** tên đơn vị, tên bài test, logo, favicon, màu chủ đạo, màu chữ, 2 font, **menu đầu trang** (thêm/bỏ link, mở tab mới), link về web chính, thông tin chân trang, **danh sách mạng xã hội / link** (thay 3 ô Facebook / YouTube / TikTok cố định).
  - **Nhúng vào web chính:** tiêu đề, mô tả, ảnh, chữ nút, **tên miền được phép nhúng**; trang hiện sẵn 3 đoạn mã kèm nút "Chép" để gửi Tuấn.
- **Trang giới thiệu – tab "Các khối nội dung":** thêm / sửa / bật-tắt / **kéo thả** / xóa (lấy lại bằng lịch sử). 5 loại khối: Cách làm bài, 6 nhóm Holland, Chữ và ảnh, **Câu hỏi thường gặp** (có sẵn 4 câu mẫu), **Cảm nhận phụ huynh / học sinh** (chỉ đăng lời thật; khối trống tự ẩn). Khối "Cách làm bài" đặt cạnh banner khi banner không có ảnh (giữ giao diện cũ).
- **Thư viện ảnh:** tải nhiều ảnh, **tự thu nhỏ còn rộng 1920px và nén** (GD có sẵn, xoay đúng chiều ảnh chụp điện thoại), thùng rác. Ô ảnh ở mọi trang có nút "Chọn ảnh" mở thư viện, tải ảnh mới ngay trong hộp chọn.
- **Trình soạn thảo:** CKEditor 5 bản 41.4.2 qua CDN (giống web chính), chỉ bật cho ô "chữ có định dạng". HTML được **lọc an toàn** (`HtmlSanitizer`: chỉ giữ đoạn, đậm, nghiêng, link http/https/mailto/tel, danh sách; bỏ script, sự kiện, iframe…) khi lưu và khi hiển thị.
- **Khối nhúng cho web chính:** `GET /api/v1/public/intro` (JSON theo hợp đồng, cache 5 phút, chỉ tên miền cho phép gọi từ trình duyệt, 60 lần/phút), `GET /embed` (khung rút gọn, header `frame-ancestors` theo danh sách tên miền), `GET /embed.js` (tự chèn iframe, tự co chiều cao). Bấm nút trong khung mở trang Holland ở **tab mới** (làm bài trên trang Holland, không làm bài trong iframe – đỡ lỗi đăng nhập / cookie giữa 2 tên miền).
- **Vai trò marketing (`EDITOR`):** chỉ thấy Bảng điều khiển, Mô tả 6 nhóm, Trang giới thiệu, Trang kết quả, Email, Nhúng vào web chính, Thư viện ảnh, Lượt làm bài. Vào mục khác → báo không có quyền; không quay lại được thay đổi câu hỏi / chấm điểm. Tài khoản mẫu tạo từ `.env` (trang quản lý tài khoản: giai đoạn 6).
- Migration chỉ thêm: bảng `media`. **146 test đều đạt** (18 test mới). Đã chụp ảnh kiểm tra trang giới thiệu ở 1366px và 496px, khối nhúng.

**Chưa làm / để sau:** SEO + ô "Mã nhúng chatbot" thuộc trang Cài đặt (giai đoạn 7); câu báo lỗi cho khách ("Vui lòng điền…") vẫn trong code – đưa vào admin ở giai đoạn 7 cùng Cài đặt.

### Bảng nghiệm thu – việc đã làm được đến giai đoạn 3c
| Vai trò | # | Việc | Trạng thái |
|---|---|---|---|
| Sếp | 1 | Đổi số hotline, địa chỉ, logo | ✅ Trang "Giao diện & chân trang" (phía web Holland) |
| Sếp | 4 | Sửa câu hỏi Holland, thêm câu "Trường chuyên?" | ✅ |
| Sếp | 5 | Đổi chữ trên trang kết quả Holland | ✅ Có xem trước |
| Sếp | 7 | Tài khoản marketing chỉ thấy phần của mình | Một phần: vai trò đã chặn đúng; tạo tài khoản trên web ở giai đoạn 6 |
| Marketing | 2 | Thay banner | ✅ Phía web Holland (ảnh banner máy tính + điện thoại); hẹn giờ banner là việc của web chính |

## Tiếp theo: Giai đoạn 4 – Kết nối CRM

Danh mục ngành từ CRM (dự phòng file `majors.json`), gửi khách sang CRM thật qua hàng đợi + thử lại + lịch chạy, trang "Hàng đợi gửi CRM" (gửi lại), trang "Kết nối CRM & chatbot" (bật/tắt, địa chỉ, mã kết nối, nút "Kiểm tra kết nối", ô mã nhúng chatbot). Test bằng `Http::fake()`.
