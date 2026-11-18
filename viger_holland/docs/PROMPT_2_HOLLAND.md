# PROMPT CLAUDE CODE – WEB 2: TRẮC NGHIỆM HOLLAND (`holland.viger.edu.vn`)

> Làm sau khi CRM chạy được ở máy (cần API của CRM để test kết nối; nếu CRM chưa sẵn thì vẫn làm được nhờ chế độ dự phòng).
> Cách dùng: tạo thư mục trống `VigerEdu_Holland` (trên ổ D), chép vào đó thư mục `database/` của gói này và cả thư mục `00_CHUNG/` (đổi tên thành `docs/`). Mở Claude Code tại thư mục đó, dán phần dưới dòng `=====`.

=====

Bạn là lập trình viên Laravel. Nhiệm vụ: tạo **web trắc nghiệm Holland riêng** cho Viger-Edu, chạy ở `holland.viger.edu.vn`. Đọc hết yêu cầu trước khi làm.

## 0. Bối cảnh

Hệ thống gồm nhiều web **tách rời**: web chính `viger.edu.vn` (Tuấn), **web này** (bài test), `crm.viger.edu.vn` (CRM, tôi làm SAU web này – hiện chưa có, nên mọi kết nối CRM phải test bằng `Http::fake()` và chạy được ở chế độ dự phòng), AI chatbot Python (Ý). Các web chỉ nói chuyện qua API trong **`docs/HOP_DONG_KET_NOI.md` – đọc file này đầu tiên, không tự đổi tên trường/URL.**

Yêu cầu của Thầy: **web Holland là công cụ riêng, đổi được cách tính** (thuật toán khác, bộ câu khác) mà CRM và chatbot không phải sửa, miễn là vẫn trả kết quả đúng cấu trúc `holland-result/1`. Web này cũng phải **chạy được khi CRM tạm hỏng** (khách vẫn làm bài và xem kết quả; khách được gửi sang CRM sau).

Tài liệu: `docs/Holland_de_xuat_nganh_v2.docx/.xlsx` (thuật toán bản chốt, giải thích từng công thức), `docs/Phan_tich_thiet_ke_he_thong_Holland.docx`, `docs/3_Mau_GoogleSheet_Viger_Holland_v2.xlsx` (tab CAU_HOI). Seed: `database/seeders/data/holland/` – `questions.json` (A01–A15 thu thập thông tin + B01–B54 Holland, câu B là **câu mẫu**), `holland_config.json`, `majors.json` (bản dự phòng của danh mục ngành), `test_cases.json` (kết quả kỳ vọng), `holland_engine_reference.php` (**bản tham chiếu thuật toán bằng PHP thuần – port sang Laravel, giữ nguyên công thức**).

## 0a. MỤC TIÊU SỐ 1: NGƯỜI KHÔNG LÀM IT DÙNG ĐƯỢC

Đọc kỹ `docs/TIEU_CHI_DE_DUNG_VA_NGHIEM_THU.md` trước khi làm. **10 quy tắc thiết kế admin ở mục 1 là bắt buộc** cho mọi màn hình; cuối mỗi giai đoạn chuẩn bị **bản chạy thử + tài khoản mẫu theo vai trò** và liệt kê các việc trong **bảng nghiệm thu mục 3** đã làm được ở giai đoạn đó. Làm luôn: nút "?" có hướng dẫn ngắn trên mỗi trang admin, trang "Hướng dẫn sử dụng" theo vai trò, tour giới thiệu lần đăng nhập đầu, và ở giai đoạn cuối viết `docs/SO_TAY_SU_DUNG.md` (mỗi việc một mục: mục đích → các bước → lỗi thường gặp; chừa chỗ chèn ảnh chụp màn hình).

## 0a2. TỪ ĐIỂN DỮ LIỆU CHUNG (bắt buộc)

Tên trường, khóa API, mã định danh và **giá trị lựa chọn** phải đúng `docs/TU_DIEN_DU_LIEU_CHUNG.xlsx` (và `docs/lua_chon_chuan.json`). Không tự đặt tên khác. Thêm trường mới thì báo tôi để cập nhật từ điển.

## 0b. NGUYÊN TẮC BẮT BUỘC: "MỞ" – KHÔNG CỐ ĐỊNH TRONG CODE

Người quản lý (Thầy) **không làm IT**: mọi thứ có thể muốn thêm/đổi sau này phải **làm được trên admin**, không sửa code. Chỉ **lõi chấm điểm** nằm trong code (và đổi được bằng engine). Cụ thể:

1. **Phần A (thông tin khách) là form động**: ADMIN thêm/sửa/ẩn/sắp xếp câu hỏi Phần A: kiểu (chữ, số, SĐT, email, ngày, chọn 1, chọn nhiều, có/không), lựa chọn, bắt buộc, gợi ý, điều kiện hiện (ví dụ chỉ hỏi "Họ tên phụ huynh" khi người làm là học sinh), và **"Gửi sang CRM vào trường"** (chọn trường chuẩn của `lead/1` hoặc gõ mã trường tùy biến → gửi trong `extra_fields`). Câu mới **tự hiện ở form, tự gửi CRM, tự có trong Excel**.
2. **Phần B mở**: thêm/bớt câu, đổi nhóm, đổi điểm tối đa, đổi **kiểu trả lời** (Đúng/Không, thang 3 hoặc 5 mức – nhãn từng mức sửa được), **nhiều bộ câu hỏi** (ví dụ bộ cho THPT, bộ cho sinh viên – chọn bộ theo câu trả lời Phần A hoặc theo link `?bo=...`), bật/tắt bộ, số câu mỗi trang.
3. **Mọi chữ, hình, khối nội dung** khách thấy sửa được (mục 6b) – có thể **thêm trang nội dung mới** (ví dụ "Giới thiệu Holland", "Câu hỏi thường gặp") và gắn vào menu.
4. **Cấu hình chấm điểm trên admin**: trọng số FIT, ngưỡng nhãn, tên nhãn, số ngành hiển thị, mô tả 6 nhóm – sửa trên trang "Cấu hình chấm điểm" (giá trị mặc định = bản chốt).
5. **Test chấp nhận bắt buộc**: ADMIN thêm câu Phần A "Trường THPT đang học có phải trường chuyên?" (có/không, gửi sang CRM mã `truong_chuyen`) + thêm 1 bộ câu B mới 30 câu thang 5 mức → khách làm bài thấy câu mới, kết quả chấm đúng với thang mới, gói gửi CRM có `extra_fields.truong_chuyen` – **không sửa code**.

## 0c. Bố cục admin

Xem `docs/anh_mau_quan_tri/H19_admin_holland.png` và mục 10 trong `docs/De_xuat_cau_truc_quan_tri_web_Viger.docx`: menu trái chia nhóm, đúng các mục trong hình; làm admin theo bố cục đó.

## 1. Quy tắc làm việc

1. Tạo project **Laravel 13** mới (PHP ≥ 8.3), MySQL khi chạy thật (SQLite cho dev/test), Blade + CSS/JS thuần. Cài Laravel Boost và theo hướng dẫn nó sinh ra.
2. Giao diện thống nhất với web chính (tham khảo `public/styles.css`, layout, header của `https://github.com/vivi123-ou/VigerEdu_Web` – clone ra thư mục khác để xem, không sửa). Header có logo + link "Về trang chủ Viger" (`viger.edu.vn`). **Thiết kế cho điện thoại trước** (phụ huynh/học sinh chủ yếu dùng điện thoại).
3. Không thêm package ngoài Laravel nếu chưa hỏi tôi. Ngoại lệ đã duyệt: **`openspout/openspout`** (Excel .xlsx).
4. Git: `git init`, làm trên `dev`, commit theo giai đoạn, tiếng Việt. Không push.
5. Làm theo **giai đoạn ở mục 8**: kế hoạch → chờ duyệt → làm → `php artisan test` + `vendor/bin/pint --dirty` → báo kết quả → dừng.
6. Giao diện tiếng Việt; tên class/bảng/cột tiếng Anh.
7. Tài liệu chỉ viết: `README.md` (cài, deploy Linux, cấu hình), `docs/API_HOLLAND.md` (curl + Python `requests`), `docs/THAY_THUAT_TOAN.md` (cách viết engine mới).
8. Chưa rõ: hỏi tôi.

## 2. Kiến trúc bên trong (để thay thế được)

- `app/Contracts/ScoringEngine.php`: `score(array $answers, Collection $majors): HollandResult`.
- `app/Engines/RiasecFitEngineV2.php`: thuật toán bản chốt (mục 4). Chọn engine bằng `config('holland.engine')`. Thêm 1 engine giả trong test để chứng minh thay được.
- `app/Data/HollandResult.php`: DTO đúng `holland-result/1` (có `toArray()`).
- `app/Contracts/LeadSink.php` → `CrmHttpLeadSink` (gọi `POST {CRM_URL}/api/v1/leads` với `X-Api-Token`, timeout 5 giây) và `NullLeadSink` (khi `HOLLAND_CRM_ENABLED=false`).
- `app/Contracts/MajorCatalog.php` → `CrmMajorCatalog` (gọi `GET {CRM_URL}/api/v1/catalog/majors`, cache 1 giờ, **lỗi thì dùng bản cache cũ hoặc `majors.json`**) và `JsonMajorCatalog`.
- **Hàng đợi gửi lại:** bảng `outbox` (attempt_id, payload, status pending/sent/failed, tries, last_error). Gửi lead qua job; thất bại thì thử lại (1, 5, 30 phút…), lệnh `php artisan holland:retry-outbox` + scheduler. Admin xem được các lượt chưa gửi.

## 3. Dữ liệu

| Bảng | Cột chính |
|---|---|
| `questions` | code (A01…/B01…), part, text, form ("Tôi là…/Tôi có thể…/Tôi thích…"), answer_type, options, lead_field (Phần A ghi vào trường nào của `lead/1`), riasec (phần B), max_score, sort_order, is_active |
| `attempts` | uuid, result_token (UUID, xem kết quả không cần đăng nhập), source (web/api), engine, engine_version, contact (json Phần A – **chỉ giữ để gửi CRM, xóa SĐT/email sau khi CRM xác nhận nếu bật `HOLLAND_PURGE_CONTACT` (mặc định tắt)**), answers (json), result (json HollandResult), crm_customer_code, crm_sync_status, appointment_at |
| `outbox` | như mục 2 |
| `users` | admin của web Holland (chỉ để sửa câu hỏi và xem thống kê), role ADMIN |

Seeder idempotent: `questions.json` (69 câu), admin từ `.env`.

## 4. Thuật toán `RiasecFitEngineV2` (KHÔNG đổi công thức)

1. `p[g] = tổng điểm câu nhóm g / tổng max_score nhóm g` (câu active) – chạy được với 54 hay 108 câu, Đúng/Sai hay Likert.
2. Mã = 3 nhóm p cao nhất; **bằng nhau xếp theo R, I, A, S, E, C**.
3. Cảnh báo (không đổi xếp hạng): `di3 = p1 − (p2 + p4)/2` (< 0,15 = hồ sơ phẳng); `consistency = s(X1,X2)`; `has_tie` khi p1=p2 hoặc p2=p3 hoặc p3=p4.
4. `s(x,y) = 3 − min(|i−j|, 6−|i−j|)` theo thứ tự "RIASEC".
5. Khớp mã: `C = 3·s(X1,Y1) + 2·s(X2,Y2) + s(X3,Y3)`, `code_match = C/18` (X = mã học sinh, Y = mã ngành).
6. Khớp điểm: `r = (3·p[Y1] + 2·p[Y2] + p[Y3] − Σp) / (√8 · √Σ(p − p̄)²)`, mẫu = 0 → r = 0; `score_match = (r+1)/2`.
7. `FIT = 0,6·code_match + 0,4·score_match` (trọng số từ config). Ngành có nhiều mã → lấy mã cho FIT cao nhất (`code_used`).
8. Nhãn: ≥0,80 Rất phù hợp · ≥0,65 Phù hợp · ≥0,50 Có thể cân nhắc · còn lại Ít phù hợp.
9. Chỉ ngành active; tách university/vocational; sắp FIT giảm → code_match giảm → code tăng; top 5 đại học + top 3 học nghề.
10. `fields` / `testas`: điểm nhóm = FIT cao nhất trong nhóm (TestAS tính cả nhóm chính và phụ). `reason` = `"AIS ↔ IAS · em đạt I 67%, A 89%, S 56%"`.

**Unit test bắt buộc theo `test_cases.json`** (5 học sinh: điểm %, mã, di3, has_tie, top 5 đại học với code_used/code_match/score_match/fit, top 3 học nghề, lĩnh vực mạnh nhất; sai số 0,001) + bằng điểm, hồ sơ phẳng, ngành 3 mã, ngành inactive.

## 5. Giao diện

- `GET /`: giới thiệu ngắn (bài test 10–15 phút, miễn phí) → **Phần A** (A01–A15 active theo sort_order; bắt buộc tick đồng ý A15; bắt buộc SĐT **hoặc** email) → **Phần B**: 54 câu, mỗi trang 6–9 câu, thanh tiến độ, lưu tạm trong trình duyệt (tải lại trang không mất bài), JS thuần.
- `POST /nop-bai` (throttle 5/phút): lưu attempt, chấm, đưa lead vào outbox (kèm `holland_result`, **`quiz` = bài làm chi tiết từng câu theo hợp đồng bản 2**, `source: "holland"`, `external_ref` = uuid), gửi email kết quả (mặc định `MAIL_MAILER=log`), chuyển tới kết quả.
- `GET /ket-qua/{token}`: biểu đồ 6 nhóm (HTML/CSS/SVG), mã + giải thích 3 chữ, cảnh báo nhẹ nếu hồ sơ phẳng/bằng điểm, lĩnh vực mạnh + 3–5 ngành (tên, mô tả ngắn, nhãn). **Không hiện FIT dạng số, không hiện GREEN/YELLOW/RED, không hứa đậu.** Nút "Đặt lịch tư vấn" (form ngày giờ → cập nhật attempt, gửi lại lead có `appointment_at`), nút Gọi / Zalo, link về `viger.edu.vn`. Chỗ gắn chatbot: `<div id="viger-chatbot"></div>` và vị trí chèn `<script>` của Ý cấu hình qua `.env` (`HOLLAND_CHAT_WIDGET_URL`, để trống thì không chèn).
- `/admin` (đăng nhập): **quản lý nội dung trang (mục 6b)**, xem từng lượt làm bài (`/admin/attempts/{id}` – có **bảng xếp hạng đầy đủ mọi ngành** kèm khớp mã, khớp điểm, FIT, lọc theo lĩnh vực / đại học / học nghề; – đây là `view_url` gửi sang CRM), quản lý câu hỏi (sửa nội dung, bật/tắt, thứ tự, nhóm, điểm tối đa), thống kê số lượt làm bài theo ngày và theo mã, danh sách outbox lỗi + nút gửi lại. **Không** có danh sách khách (đó là việc của CRM).
- **Bộ câu hỏi bằng Excel** (để Thầy soạn câu dễ): nút "Tải bộ câu hỏi" → file `.xlsx` đúng cột tab `CAU_HOI` của sheet mẫu; Thầy sửa trong Excel → "Nhập bộ câu hỏi" có bước **xem trước** (báo lỗi theo dòng: thiếu nhóm RIASEC, mã trùng…), khớp theo Mã câu, không xóa câu thiếu (chỉ tắt `is_active`), lưu lịch sử nhập.
- Nút "Xuất kết quả làm bài (.xlsx)": mỗi lượt một dòng – ngày, nguồn, mã Holland, 6 điểm %, top 3 ngành, trạng thái gửi CRM (không xuất SĐT/email – thông tin liên hệ xem ở CRM).

## 6. API (`/api/v1`, đúng hợp đồng mục 4)

`GET /questions?part=A|B`, `POST /score` (không lưu, không gửi CRM; tham số `full=1` trả thêm `ranking[]` = **toàn bộ ngành đang bật** đã xếp hạng, mỗi dòng có major_code, name, field, code_used, code_match, score_match, fit, label – dùng cho nút "Xem xếp hạng đầy đủ" của CRM). Token qua bảng `api_clients` (hash, `hash_equals`), throttle 60/phút, lỗi JSON 401/422/429. `docs/API_HOLLAND.md` có ví dụ curl và Python `requests`, và luồng mẫu cho chatbot: lấy câu hỏi → chấm → gửi lead sang CRM kèm `holland_result`.

## 6b. Quản trị nội dung trang – Thầy sửa trên web, không vào code

Thầy không làm IT, nên **mọi chữ, hình, màu khách nhìn thấy đều sửa được trong `/admin`**:
- **Giao diện chung:** logo, favicon, màu chủ đạo, font (chọn từ danh sách), **menu đầu trang** (thêm/sửa/xóa/sắp xếp link), **chân trang** (thông tin công ty: tên, địa chỉ, hotline, email, giờ làm việc, mạng xã hội, bản quyền).
- **Trang giới thiệu bài test:** **banner** (ảnh nền máy tính + ảnh điện thoại, tiêu đề, mô tả, chữ trên nút), các **khối nội dung** sắp xếp được (khối chữ + ảnh, khối "Cách làm 3 bước", khối câu hỏi thường gặp, khối cảm nhận phụ huynh), bật/tắt từng khối.
- **Trang kết quả:** lời chào, đoạn giải thích 6 nhóm Holland (chữ của từng nhóm R/I/A/S/E/C sửa được), lời kêu gọi đặt lịch, chữ cảnh báo khi hồ sơ phẳng.
- **Email gửi khách:** tiêu đề, lời mở đầu, chữ ký.
- **Khối cho web chính** (`/api/v1/public/intro`): tiêu đề, mô tả, ảnh, chữ nút.
- Soạn chữ bằng **trình soạn thảo đơn giản** (đậm, nghiêng, link, danh sách) – có thể dùng CKEditor 5 bản CDN (giống web chính) – lọc HTML an toàn khi lưu. Ảnh: tải lên, tự thu nhỏ/nén, lưu `storage/app/public/media`, có **thư viện ảnh** để chọn lại.
- Có nút **"Xem trước"** trước khi lưu và **lịch sử thay đổi** (quay lại bản trước).
- Mặc định (khi cài mới) có sẵn nội dung mẫu đẹp, đúng tiếng Việt, để không trang nào trống.
- **Ô "Mã nhúng chatbot"** (trong Cài đặt): dán đoạn `<script>` của chatbot – để trống là không có chatbot; chatbot hoàn toàn độc lập.

## 6c. Nhúng vào web chính của Tuấn

- `GET /api/v1/public/intro` (không cần token, có cache, CORS cho phép tên miền web chính trong Cài đặt): trả khối giới thiệu (mục 6b) + tổng số lượt làm bài.
- `GET /embed`: bản rút gọn (không header/footer) để đặt trong iframe; `GET /embed.js`: script nhỏ tự chèn iframe vào phần tử được chỉ định và tự co giãn chiều cao (postMessage). Danh sách tên miền được phép nhúng sửa trong Cài đặt (header `Content-Security-Policy: frame-ancestors`). Cookie phiên dùng `SameSite=None; Secure` cho đường dẫn nhúng.

## 7. Bổ sung: bật/tắt, kết nối và cài đặt KHÔNG cần sửa code

- **Trang "Cài đặt" trong admin** (ADMIN), lưu trong bảng `settings` (key/value, có cache), **ưu tiên hơn `.env`** cho mọi thứ người quản lý cần đổi: tên đơn vị, logo, màu chủ đạo, hotline, Zalo, email gửi đi, link web chính, mã Google Analytics / Google Search Console, và **phần kết nối** (bật/tắt từng kết nối, địa chỉ web bên kia, token, nút "Kiểm tra kết nối" gọi thử API và báo xanh/đỏ). Không ghi cứng tên miền hay chữ "Viger" trong code/view – lấy từ cài đặt.
- **Gỡ / thay một công cụ = tắt kết nối trong trang Cài đặt** (hoặc thu hồi token), không sửa code. Công cụ còn lại tự chạy chế độ dự phòng.
- **SEO:** title + meta description từng trang (sửa được trong Cài đặt), Open Graph, `sitemap.xml`, `robots.txt`; trang công khai có H1/H2 đúng cấu trúc; trang kết quả cá nhân và toàn bộ trang admin đặt `noindex`; chèn mã Analytics từ Cài đặt.
- **Cài lên host Linux trắng (VPS) do nhóm tự quản lý:** README có mục từng bước: cài nginx, PHP-FPM 8.3 + extension cần thiết, MySQL/MariaDB, Composer, tạo database + user riêng, tạo server block cho tên miền con trỏ vào `public/`, chứng chỉ HTTPS bằng Certbot, quyền thư mục `storage/` `bootstrap/cache/`, cron `* * * * * php artisan schedule:run`, supervisor (hoặc systemd) chạy queue worker; mỗi web chạy bằng user Linux riêng; cách cập nhật phiên bản mới (`git pull`, `composer install --no-dev`, `php artisan migrate --force`, xóa cache) và cách quay lại bản trước. Kèm file mẫu cấu hình nginx trong `deploy/`.
- **Trang cài đặt lần đầu** `/install` (tự khóa sau khi xong): kiểm tra yêu cầu máy chủ (PHP, extension, quyền ghi), nhập database, tạo admin, nạp dữ liệu mẫu, sinh token.

## 8. Giai đoạn

1. Khởi tạo Laravel, Boost, layout mobile-first, admin login, seeder câu hỏi (69).
2. Contracts + `RiasecFitEngineV2` + `JsonMajorCatalog` + unit test theo `test_cases.json` + test engine giả.
3. **Form Phần A động + nhiều bộ câu hỏi + cấu hình chấm điểm trên admin (mục 0b)** + trang làm bài + kết quả + email + đặt lịch + **quản trị nội dung trang (6b)** + khối nhúng (6c). Feature test: nộp hợp lệ → attempt + kết quả + outbox; thiếu đồng ý / thiếu cả SĐT lẫn email → lỗi; token sai → 404.
4. Kết nối CRM: `CrmMajorCatalog`, `CrmHttpLeadSink`, outbox + retry + scheduler. Test bằng `Http::fake()`: CRM trả 200 → `crm_customer_code` được lưu; CRM lỗi/timeout → khách vẫn thấy kết quả, outbox pending, retry gửi được.
5. API v1 + `API_HOLLAND.md`. Test: sai token 401; `/score` với HS01 → mã AIS, ngành 1 N043, fit ≈ 0,8011.
6. **An toàn dữ liệu**: migration chỉ thêm, chặn `migrate:fresh`/`reset`/`db:wipe` khi production; `php artisan holland:backup` chạy mỗi đêm (giữ 30 bản) + `holland:restore`; `HOLLAND_PURGE_CONTACT` **mặc định false** (chỉ bật khi CRM đã có sao lưu đều đặn); README mục "Gỡ / cài lại web Holland": gỡ không ảnh hưởng CRM (khách đã gửi nằm trong database CRM), lượt chưa gửi nằm trong outbox và được gửi khi cài lại.
7. Admin câu hỏi/thống kê/outbox, xuất/nhập Excel (test: file xuất mở lại có đúng tab, đúng thứ tự cột như tab CAU_HOI; file nhập có dòng sai → xem trước báo đúng dòng, không ghi gì; file đúng → cập nhật, không xóa câu thiếu), README (deploy Linux: PHP 8.3, MySQL, document root `public/`, HTTPS, cron cho scheduler, queue worker), `THAY_THUAT_TOAN.md`, chạy toàn bộ test. Báo cáo file đã tạo, việc còn mở, câu hỏi.

Mục 7 làm xen vào các giai đoạn phù hợp (Cài đặt + kết nối ở giai đoạn kết nối/API; SEO ở giai đoạn giao diện; /install và tài liệu VPS ở giai đoạn cuối).

Bắt đầu bằng việc đọc `docs/HOP_DONG_KET_NOI.md`, `holland_engine_reference.php`, `test_cases.json`, rồi gửi tôi kế hoạch giai đoạn 1.
