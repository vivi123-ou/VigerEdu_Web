# PROMPT 3 – CẬP NHẬT WEB APP HOLLAND THEO BẢN CAREER MAP CỦA THẦY

> **Cách dùng:**
> 1. Chép thư mục `docs/` và `database/` của gói này vào repo Holland đang có, ghi đè lên chỗ trùng tên. Không xóa file cũ.
> 2. Mở Claude Code tại thư mục repo.
> 3. Dán toàn bộ phần dưới dòng `=====`.
> 4. Mỗi giai đoạn Claude Code sẽ dừng lại chờ bạn duyệt.

=====

Bạn đang sửa một **repo Laravel đã có** (web Holland của VIGER EDU, làm theo `docs/PROMPT_2_HOLLAND.md`). Nhiệm vụ: đổi thuật toán và giao diện sang **bản kiến trúc Career Map của Thầy**, giữ lại những phần đang chạy tốt.

Trước khi viết code, hãy đọc:

- `docs/holland_v3/HollandEngine.php`: bản tham chiếu thuật toán, PHP thuần. Kết quả phải giống hệt file này.
- `docs/holland_v3/cases.json`: 8 học sinh mẫu và kết quả kỳ vọng. Đây là bài kiểm tra bắt buộc.
- `docs/holland_v3/ban_mau_giao_dien.html`: bản mẫu giao diện, mở bằng trình duyệt. Làm giao diện theo đúng luồng và bố cục ở đây.
- `docs/holland_v3/Cau_truc_web_app_Holland.docx`: giải thích nghiệp vụ.
- `database/seeders/data/holland_v3/*.json`: dữ liệu chuẩn.

## 0. Quy tắc an toàn (bắt buộc, không ngoại lệ)

1. Làm trên nhánh `dev`. Tạo nhánh `feature/holland-v3` từ `dev`. **Không push.** Commit theo giai đoạn, lời commit bằng tiếng Việt.
2. **Không commit `.env`**. Kiểm tra `.gitignore` trước lần commit đầu. Khóa API, mật khẩu, `CRM_URL` chỉ nằm trong `.env`.
3. **Migration chỉ thêm**: chỉ thêm bảng mới hoặc cột mới cho phép rỗng.
   - Không xóa, không đổi tên bảng hay cột cũ.
   - Dữ liệu lượt làm bài cũ phải còn xem được (đánh dấu `engine = fit_v2`).
4. **Không ghi số điện thoại, email, họ tên vào log** (kể cả log lỗi và log gửi CRM). Nếu cần thì che bớt, ví dụ `09xx xxx 678`.
5. Trang `/admin` và mọi trang có dữ liệu khách: gắn `noindex, nofollow` và bắt buộc đăng nhập. `robots.txt` chặn `/admin`.
6. Không thêm package mới nếu chưa hỏi tôi.
7. Mỗi giai đoạn theo thứ tự:
   1. Báo kế hoạch rồi chờ tôi duyệt.
   2. Làm.
   3. Chạy `php artisan test` và `vendor/bin/pint --dirty`.
   4. Báo kết quả rồi dừng.
8. Chỗ nào chưa rõ thì hỏi tôi, không tự đoán.

## 1. Cái gì đổi, cái gì giữ

| Phần | Bản cũ (PROMPT 2) | Bản mới (PROMPT 3) |
|---|---|---|
| Kiểu trả lời | Đúng/Sai, có `max_score` | **Thang 1–5** (5 mức) và thêm lựa chọn **"Không biết"** |
| Câu hỏi | B01–B54, 9 câu/nhóm, cố định | Mã **R01…C09**, 9 câu/nhóm trong ngân hàng. Mỗi lượt lấy **6 câu/nhóm = 36 câu**: bộ cố định hoặc rút ngẫu nhiên có cân bằng (admin chọn) |
| Điểm nhóm | `p = tổng / tổng max` | **Trung bình** các câu đã trả lời (1–5). "Không biết" không tính vào mẫu số |
| Mã kết quả | 3 chữ luôn hiện | **2 chữ luôn hiện** (có thứ tự: IS khác SI). Chữ thứ 3 chỉ đọc chi tiết cho khách khi điểm nhóm 3 > 4,2 và nhóm đó đủ 6 câu. Tư vấn viên và quản trị luôn thấy đủ 3 chữ |
| Gợi ý ngành | FIT = 0,6·code_match + 0,4·score_match; nhãn "Rất phù hợp…" | **Bỏ FIT** (không có nguồn công bố). Dùng **chỉ số C** (Brown & Gore 1994), 0–18. Thứ tự: 2 ngành nền của cặp, rồi các ngành khác có chữ đầu thuộc nhóm "Quan tâm" (> 3,40) |
| Nhãn ngành | Rất phù hợp / Phù hợp / … | Liên quan nhiều (C ≥ 15) / Liên quan (≥ 11) / Có thể khám phá (≥ 7). **Không bao giờ hiện "% phù hợp"** |
| Học nghề | top 3 học nghề (vocational) | **Bỏ.** Chỉ còn đại học: Uni / HAW (đại học ứng dụng) |
| Mã ngành | danh mục tự đặt | **HRK SIT Studienfelder** (mã lĩnh vực chính thức của Đức) trong `majors.json`. Ngành không có mã SIT ghi `sit_code = null` |
| Hồ sơ | di3, has_tie | Quan hệ lục giác (liền kề / cách một / đối kháng), độ nhất quán, mã thay thế khi sát điểm (≤ 0,25), trục Prediger (chỉ tư vấn viên xem), 7 cờ chất lượng bài làm |
| Phần A | A01–A15 trong bảng `questions` | **Trường thông tin động** (`form_fields`): 18 trường, chỉ 4 bắt buộc (họ tên, SĐT, năm sinh, đồng ý). Có cờ `to_crm` / `on_report` / `visible` |

**Giữ nguyên, không viết lại:**

- Đăng nhập admin.
- Hàng đợi `outbox` và lệnh gửi lại sang CRM.
- Quản trị nội dung trang (mục 6b cũ).
- Xuất và nhập Excel bộ câu hỏi (chỉ thêm cột mới).
- `result_token`.
- Header và giao diện theo web chính.
- Interface `ScoringEngine`: thêm engine mới, **không xóa** `RiasecFitEngineV2`. Đặt `config('holland.engine')` mặc định là `average_v3`.

## 2. Cấu trúc hệ thống (4 lớp)

```
Giao diện   resources/views/student/*   (Chọn bài → Thông tin → Làm bài → Kết quả)
            resources/views/admin/*     (Cài đặt · Câu hỏi · 15 cặp · 120 mã · Ngành · Trường thông tin · Lượt làm bài)
            routes/api.php              (questions · score · result · catalog)
Trình bày   app/Services/Holland/ReportPresenter.php   → 3 góc nhìn: khach / tuvan / admin
            app/Services/Holland/FormSchema.php        → dựng form từ form_fields, kiểm tra hợp lệ
Tính toán   app/Services/Holland/QuestionPicker.php    → chọn 36 câu (fixed | random_balanced), lưu danh sách câu vào attempt
            app/Services/Holland/ScoringService.php    → trung bình nhóm, xếp hạng, code2/code3, mã thay thế
            app/Services/Holland/ProfileAnalyzer.php   → quan hệ lục giác, nhất quán, Prediger, 7 cờ chất lượng
            app/Services/Holland/CodeCatalog.php       → 15 cặp + 6 chữ thứ ba → tự sinh 120 lời giải thích
            app/Services/Holland/MajorMatcher.php      → chỉ số C, xếp thứ tự ngành
            app/Engines/AverageEngineV3.php            → implements ScoringEngine, gọi 4 service trên
            app/Services/Holland/CrmSync.php           → đóng gói kết quả gửi CRM qua outbox (chỉ trường to_crm có giá trị)
Dữ liệu     bảng ở mục 3, seed từ database/seeders/data/holland_v3/*.json
```

Luồng một lượt làm bài:

1. `QuestionPicker` chọn 36 câu.
2. Học sinh trả lời, mỗi câu lưu `value` (1–5 hoặc null = Không biết) và `ms` (thời gian trả lời).
3. `AverageEngineV3` → kết quả `holland-result/3`.
4. Lưu `attempts.result_json`.
5. `CrmSync` đưa vào `outbox`.
6. `ReportPresenter` hiện kết quả theo vai trò.

## 3. Dữ liệu (migration chỉ thêm)

| Bảng mới | Cột chính | Seed |
|---|---|---|
| `riasec_types` | code, name_vi, name_de, mo_ta, moi_truong, sort_order | `riasec_types.json` (6) |
| `riasec_pairs` | pair (khóa không thứ tự, ví dụ IS), relation, distance, ten_cap, giai_thich, majors (json id), occupations (json id) | `riasec_pairs.json` (15) |
| `third_letters` | code, y_nghia, goi_y | `third_letters.json` (6) |
| `majors` | id, name_vi, name_de, sit_code, sit_name, sit_match (exact/closest/null), codes (json, ví dụ ["ISC","SIC"]), level (uni/haw/both), active, sort_order | `majors.json` (46) |
| `occupations`, `major_occupation` | id, name_vi, name_de, code / major_id, occupation_id | `occupations.json` (51), `major_occupation.json` (53) |
| `environments` | code, ten, mo_ta | `environments.json` (14) |
| `form_fields` | key, label, type, group, required, to_crm, on_report, visible, options (json), sort_order | `form_fields.json` (18) |
| `holland_settings` | key, value (json), updated_by | `settings.json` |
| `field_values` | attempt_id, field_key, value | – |

**Thêm cột** (nullable):

- Bảng `questions`: `group` (R…C), `facet`, `variant`, `scale` (likert5).
- Bảng `answers` (hoặc json trong attempts): `value` (tinyint nullable), `ms` (int).
- Bảng `attempts`: `engine`, `engine_version`, `question_codes` (json), `result_json`, `reliability`.

Seed câu hỏi: `questions.json` có 54 câu **mẫu** (R01–C09). Thầy sẽ thay nội dung câu qua admin hoặc Excel, giữ nguyên mã.

**Mọi số trong `settings.json` sửa được trên trang Cài đặt**, không viết cứng trong code:

- ngưỡng chữ thứ ba 4,2;
- số câu mỗi nhóm 6;
- khoảng sát điểm 0,25;
- mốc "Quan tâm" 3,40;
- các mức điểm;
- nhãn C;
- số ngành hiện cho khách 5;
- ngưỡng các cờ.

## 4. Thuật toán `AverageEngineV3`

Chép đúng logic của `docs/holland_v3/HollandEngine.php`, tách thành các service ở mục 2.

1. Điểm nhóm `mean[g]` = trung bình các câu có giá trị (1–5). Câu "Không biết" không vào mẫu số. Nhóm có dưới 4 câu hợp lệ thì `enough = false`.
2. Mức điểm:

   | Điểm trung bình | Mức |
   |---|---|
   | ≤ 1,80 | Rất ít quan tâm |
   | ≤ 2,60 | Ít quan tâm |
   | ≤ 3,40 | Quan tâm trung bình |
   | ≤ 4,20 | Quan tâm |
   | > 4,20 | Rất quan tâm |

3. Xếp hạng theo thứ tự ưu tiên: nhóm đủ dữ liệu trước, rồi `mean` giảm dần. Bằng điểm thì theo thứ tự R I A S E C.
4. `code2` = g1g2 (có thứ tự). `code3` = g1g2g3.
   `third_detail` = `mean[g3] > 4,2` **và** g3 đủ 6 câu (cả hai ngưỡng lấy từ settings).
5. Mã thay thế khi sát điểm (≤ 0,25):
   - g3 và g4 sát → thêm g1g2g4;
   - g2 và g3 sát → thêm g1g3g2;
   - g1 và g2 sát → thêm g2g1g3.
6. Khoảng cách lục giác `d(a,b) = min(|i−j|, 6−|i−j|)` theo thứ tự RIASEC.
   - Quan hệ: 1 = Liền kề, 2 = Cách một, 3 = Đối kháng.
   - Độ nhất quán (từ cặp 1–2): Cao / Trung bình / Thấp.
7. Chỉ số C giữa mã học sinh X và mã ngành Y: `C = 3·s(X1,Y1) + 2·s(X2,Y2) + 1·s(X3,Y3)` với `s = 3 − d`. Ngành có nhiều mã thì lấy mã cho C cao nhất. Nếu bằng C thì lấy `w = 3·mean[Y1] + 2·mean[Y2] + mean[Y3]` cao hơn.
8. Thứ tự ngành:
   1. Các ngành nền của cặp (`riasec_pairs.majors`), xếp theo C rồi w.
   2. Các ngành `active` khác có `mean[chữ đầu mã ngành] > 3,40` và C ≥ 7, xếp theo C, rồi w, rồi id.
   3. Khách thấy tối đa `max_majors_public` ngành. Tư vấn viên thấy hết.
9. Trục Prediger:
   - `X = Σ mean·cos(i·60°)`, `Y = Σ mean·sin(i·60°)`, i theo RIASEC.
   - `strength = √(X²+Y²)`.
   - `elevation` = trung bình 6 nhóm. `differentiation` = max − min.
10. Cờ chất lượng:

    | Cờ | Điều kiện | Mức |
    |---|---|---|
    | nhieu_khong_biet | Tổng "Không biết" > 6 | nhẹ |
    | nhom_thieu_du_lieu | 1 nhóm thiếu dữ liệu | nhẹ |
    | nhom_thieu_du_lieu | Từ 2 nhóm thiếu dữ liệu | nặng |
    | mot_muc_chiem | Một mức chiếm ≥ 90% số câu | nặng |
    | chuoi_giong_nhau | ≥ 10 câu liên tiếp cùng mức | nhẹ |
    | qua_nhanh | Trung bình < 2 giây/câu | nặng |
    | doan_bam_voi | ≥ 5 câu liên tiếp dưới 1 giây | nhẹ |

    Độ tin cậy:
    - có cờ nặng → "Chưa đủ tin cậy";
    - chỉ có cờ nhẹ → "Cần xem lại";
    - không có cờ → "Tin cậy".

    Khi "Chưa đủ tin cậy": **khách không thấy danh sách ngành**, chỉ thấy lời mời làm lại hoặc đặt lịch.
11. Kết quả theo schema `holland-result/3`, đúng các khóa của file tham chiếu:
    - `groups`, `order`, `code2`, `code3`;
    - `third_detail`, `third`, `alternates`;
    - `relations`, `consistency`;
    - `profile`, `majors`, `flags`.

**Test bắt buộc** (`tests/Feature/HollandV3Test.php`):

- Đọc `docs/holland_v3/cases.json`. Với cả 8 ca: `code2`, `code3`, `third_detail`, `alternates`, `consistency`, `flags.reliability` và **danh sách ngành theo đúng thứ tự** (id, code, c) phải khớp. Sai số điểm 0,0001.
- Test thêm:
  - Bằng điểm ở mọi vị trí.
  - Tất cả "Không biết".
  - Đổi ngưỡng 4,2 → 4,0 trong settings thì `third_detail` của ca HS01 đổi (chứng minh không viết cứng).
  - Engine cũ `fit_v2` vẫn chấm được lượt cũ.

## 5. Giao diện (theo `ban_mau_giao_dien.html`)

**Học sinh** (thiết kế cho điện thoại trước):

1. **Chọn bài**: giới thiệu ngắn (36 câu, khoảng 8 phút, không có đúng sai).
2. **Thông tin**:
   - 4 ô bắt buộc nằm trên.
   - Các nhóm không bắt buộc (Liên hệ, Cá nhân, Học tập, Mục tiêu, Marketing) gập lại.
   - Ô điểm các môn.
   - Ô đồng ý **không tick sẵn** (NĐ 356/2025).
   - Báo lỗi ngay dưới từng ô.
3. **Làm bài**:
   - Mỗi màn hình **1 câu**, 5 nút mức điểm, nút "Không biết" (bật/tắt ở Cài đặt).
   - Thanh tiến độ. Chọn xong tự sang câu sau.
   - Ghi thời gian trả lời từng câu.
   - Nếu bấm quá nhanh 3 câu liền thì hiện nhắc nhẹ.
   - **Chặn nhảy hai câu** khi vừa chọn vừa bấm "Câu tiếp theo".
4. **Kết quả cho khách**:
   - Mã 2 chữ to, kèm tên nhóm và một câu về quan hệ của 2 nhóm.
   - Khung "nhóm thứ ba": giải thích chi tiết nếu đủ điều kiện; nếu chưa thì ghi "đang hình thành".
   - Biểu đồ thanh 6 nhóm, có vạch ngưỡng 4,2.
   - Lục giác.
   - Tối đa 5 ngành (tên Việt + Đức + nhãn).
   - Nút: Đặt lịch tư vấn / Bổ sung thông tin / Làm lại.
   - Không hiện số C, không hiện %.

**Tư vấn viên** (thẻ khách, đọc qua API từ CRM; Holland chỉ cung cấp `GET /api/v1/results/{token}?view=tuvan`, có token):

- Mã 3 chữ đầy đủ.
- Lý do khách chỉ thấy 2 chữ.
- Mã thay thế, quan hệ 1–2 / 1–3 / 2–3, độ nhất quán.
- Cờ chất lượng kèm gợi ý hỏi lại.
- Toàn bộ ngành, mỗi ngành có mã, C và nguồn (cặp hay danh mục).
- Trường khách: **chỉ hiện trường có giá trị**, ghi "N trường chưa điền đang được ẩn".

**Quản trị**, gồm các tab:

- **Cài đặt**: sửa số, thấy ngay ảnh hưởng trên 8 ca mẫu.
- **Câu hỏi**: ngân hàng 54 câu, chế độ fixed hoặc random_balanced.
- **15 cặp**.
- **120 mã**: tự sinh, có ô lọc.
- **Ngành**: bật/tắt, sửa mã, mã SIT.
- **Trường thông tin**: các cột bắt buộc / gửi CRM / trên báo cáo / hiện.
- **Lượt làm bài**: bảng điểm đầy đủ, cờ, thời gian từng câu.

Mỗi tab có nút "?" giải thích bằng lời thường.

## 6. API và CRM

- `GET /api/v1/questions`: trả 36 câu đã chọn và `attempt_token`.
- `POST /api/v1/score`: không lưu; dùng cho test và CRM.
- `GET /api/v1/results/{token}?view=khach|tuvan`
- `GET /api/v1/catalog/{types|pairs|third|majors}`
- Gói gửi CRM: thêm `holland_result` (schema `holland-result/3`) và `fields`. **Chỉ gửi trường `to_crm = true` và có giá trị.** Giữ `source`, `external_ref`, `quiz` như hợp đồng cũ, thêm `ms` vào từng câu của `quiz`.

## 7. Giai đoạn

| GĐ | Việc | Xong khi |
|---|---|---|
| 1 | Migration chỉ thêm, seeder v3, model, trang Cài đặt đọc/ghi `holland_settings` | `php artisan migrate --seed` chạy 2 lần không lỗi, dữ liệu cũ còn nguyên |
| 2 | 6 service ở mục 2, `AverageEngineV3`, test 8 ca | 8/8 ca khớp, engine cũ vẫn chạy |
| 3 | Giao diện học sinh 4 bước theo bản mẫu, `FormSchema`, `QuestionPicker` | Làm trọn một lượt trên điện thoại 390px, không tràn ngang |
| 4 | Kết quả 3 góc nhìn (`ReportPresenter`), API results | Khách không thấy chữ 3 khi chưa đủ điều kiện, tư vấn viên thấy đủ |
| 5 | Admin: Câu hỏi, 15 cặp, 120 mã, Ngành, Trường thông tin, Lượt làm bài; nhập/xuất Excel thêm cột mới | Test chấp nhận ở mục 8 |
| 6 | `CrmSync` qua outbox, kiểm tra log không có SĐT/email, noindex, README | Gói gửi CRM không có trường rỗng |

## 8. Test chấp nhận (không sửa code)

1. Admin đổi ngưỡng chữ thứ ba 4,2 → 4,0. Học sinh mẫu ISA (A = 4,17) bây giờ thấy chữ A chi tiết.
2. Admin thêm trường "Trường chuyên?" (có/không, gửi CRM). Trường hiện ở form, có trong gói CRM khi được điền, không có khi bỏ trống.
3. Admin tắt ngành "Psychologie". Ca HS01 không còn ngành này; ngành tiếp theo lên thay.
4. Admin chuyển chế độ câu hỏi sang random_balanced. Hai lượt liên tiếp có bộ câu khác nhau nhưng vẫn đủ 6 câu mỗi nhóm.
5. Một lượt bấm toàn mức 3 thì ra "Chưa đủ tin cậy" và khách không thấy danh sách ngành.
