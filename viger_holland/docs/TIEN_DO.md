# Tiến độ

(Claude Code cập nhật sau mỗi phiên: đã xong · đang làm · câu hỏi cho Vân)

## Quyết định 14/11/2026 (thay các quyết định 11/11 khi khác nhau)

Xem `docs/THIET_KE_TONG_THE_V1.md` mục 0:
- **Làm bài trước, điền thông tin sau** để xem báo cáo đầy đủ.
- **OTP qua email.**
- Làm bài **6 câu / trang, chấm ngôi sao**.
- **Vai trò:** Quản trị viên > Nhân viên > Tư vấn viên (Thầy chốt lại 18/11). Chỉ quản trị viên xem toàn bộ và xuất dữ liệu.
- **CRM nội bộ riêng, đa kênh.**
- Giao diện theo logo, màu viger.edu.vn (`docs/mau_giao_dien_v3/`).
- Code chia module, có chú thích.

**Giai đoạn mới:** H1–H7 (Holland), C1–C8 (CRM). Thứ tự ở THIET_KE mục 10.

**Quyết định thêm của Vân sau mẫu giao diện (đến 17/11):** ghi ở `CLAUDE.md` mục 2:
- báo cáo khách chỉ xem trong tab, không email link / PDF / chia sẻ / họ tên;
- form 7 ô kiểu UEH, vai trò `KHAC`, đồng ý bằng một câu;
- câu hỏi xưng "Tôi", 5 nhãn sao;
- đầu trang chỉ có logo.

| Giai đoạn | Trạng thái |
|---|---|
| H1. Dọn phần đang dở, bảng dữ liệu, seeder 300 câu, danh mục, `crm_key`, `roles/permissions` | ✅ Xong (17/11) |
| F. Nền trang quản trị Filament, bỏ vai trò Marketing | ✅ Xong (17/11) |
| F2. Admin theo mẫu `admin-filament.html`: tên theo việc làm, quyền "Được…", nút "?", lối tắt, "Sắp có", 404 | ✅ Xong (17/11) |
| H2. Module Assessment: rút đề, chấm, phân tích, gợi ý ngành + 8 ca | ✅ Xong (17/11) |
| H3. Giao diện khách: giới thiệu, làm bài 6 câu/trang, kết quả sơ lược | ✅ Xong (18/11) |
| H4. Form thông tin, OTP email, báo cáo đầy đủ | H4a (phía khách) ✅ 18/11 · H4b (admin) tiếp theo |
| H5. CrmSync + Tracking | Chưa làm |
| H6. Admin (Filament): lượt làm bài, danh mục, trường thông tin, nhật ký; chuyển nốt trang nội dung cũ | Chưa làm |
| H7. API chatbot, sao lưu, tài liệu, nghiệm thu | Chưa làm |

### Vân chốt 18/11 (H4)

- Form thêm **Điện thoại / Zalo của phụ huynh, bắt buộc**. SĐT học sinh giữ lại, không bắt buộc, ẩn khi người điền chọn "Phụ huynh" (khi đó ô Email thành "Email của học sinh").
- Báo cáo đầy đủ **chỉ hiện một lần** ngay sau khi nhập đúng mã; tải lại cũng mất.
- **Bỏ câu đồng ý** (web khác không có). Câu đồng ý thành ô cài đặt `report_consent_text`, để trống; gõ vào thì câu hiện dưới nút và được lưu lại.
- Câu chưa chọn ghi "Chưa chọn câu trả lời".
- **Ngày sinh chọn trên lịch** (thay ô năm sinh): lịch có sẵn của trình duyệt, không cài thêm thư viện. Holland lưu đủ ngày sinh (mã hóa); CRM vẫn nhận `birth_year`. **Cần sửa từ điển dữ liệu chung** (`TU_DIEN_DU_LIEU_CHUNG.xlsx` đang ghi "Năm sinh – Chỉ lưu năm"): thêm `birth_date`, báo CRM, web chính, chatbot.

- **Khách luôn chỉ thấy mã 2 chữ** (kể cả khi nhóm thứ 3 trên 4,2); admin / tư vấn viên thấy đủ 3 chữ. Khác quy tắc Thầy chốt 11/11 nên làm thành công tắc "Cho khách thấy chữ thứ 3" trong "Cách bốc đề & chấm điểm", mặc định tắt; Thầy muốn quy tắc cũ thì bật. Cần báo Thầy.

### H4a đã làm (18/11/2026): phía khách

- **Form** `/nhan-bao-cao`: 8 ô dựng từ `form_fields`, 2 cột (điện thoại 1 cột). Ô có điều kiện ẩn / đổi chữ ngay khi chọn (cột mới `form_fields.conditions`), máy chủ kiểm tra lại theo cùng điều kiện. Báo lỗi tiếng Việt dưới từng ô ("Vui lòng nhập điện thoại / Zalo của phụ huynh.", "… chưa đúng. Ví dụ: 0912 345 678."). SĐT `+84 912.345.678` tự chuẩn hóa thành `0912345678`. Turnstile chạy ẩn khi có khóa trong `.env`.
- **Mã xác nhận** `/xac-nhan`: 6 số qua email, hiệu lực 5 phút, sai 5 lần khóa 30 phút **theo email** (làm lượt mới cũng không mở khóa sớm), gửi lại sau 60 giây, tối đa 5 mã / giờ / email. Sửa email gõ nhầm thì gửi ngay tới email mới. Email chỉ có mã. Mọi con số và câu chữ trong cài đặt.
- **Báo cáo đầy đủ** `/bao-cao` theo màn hình 4 của mẫu: 01 nhóm sở thích (mã, giải thích 2 nhóm, chữ thứ 3 khi đủ điều kiện, khi hai nhóm kết hợp, lục giác), 02 mức độ 6 nhóm (nhãn chữ), 03 ngành tại Đức (nhãn chữ, lý do, nghề tham khảo), 04 bước tiếp theo (nút Zalo khi có số Zalo, nút gọi hotline), "Làm lại với câu hỏi mới", lưu ý. Không họ tên, không %, không PDF, không chia sẻ, không lưu bộ nhớ đệm. Mở lại → "Báo cáo đã đóng" + nút "Làm bài lại".
- **Lưu dữ liệu:** bảng `respondents` (họ tên, email, SĐT, SĐT phụ huynh **mã hóa** + cột băm để tra cứu), `otp_codes` (chỉ lưu băm của mã), `consents`; `field_values` giờ cũng mã hóa. Hồ sơ chỉ tạo / sửa **sau khi** nhập đúng mã, cùng email thì dùng chung hồ sơ.
- **Sửa lỗi giới hạn số lần gửi:** trước đây các route `throttle:5,1`, `throttle:30,1` dùng chung một bộ đếm theo IP, khách làm xong 6 trang bài sẽ bị chặn ở form. Nay mỗi việc một bộ đếm riêng.
- Máy chạy thử: trang nhập mã hiện luôn mã (chỉ khi `APP_ENV=local`), email vào hộp thư thử cổng 1025. Database chạy thử của Vân đã thêm bảng mới, ô SĐT phụ huynh và chữ mặc định.
- **Kiểm thử: 121/121 đạt** (thêm `ReportFlowTest` 14 ca: form bắt buộc / định dạng, phụ huynh điền, trọn luồng, chỉ hiện một lần, khóa sau 5 lần sai, hết hạn và gửi lại, sửa email, giới hạn mã / giờ, gộp hồ sơ, câu đồng ý, Turnstile, lỗi gửi email không ghi email vào log, trọn hành trình qua HTTP không bị chặn).

**Cần Vân / Thầy / team:**
1. **Đoạn giải thích 6 nhóm và 15 cặp:** đã nạp ngày 18/11 (Vân soạn với Claude chat), cần Thầy duyệt. Đoạn RA đã sửa lại (bản đầu tả nhầm thành nghiên cứu + sáng tạo). 6 đoạn "Chữ thứ ba" và tên hướng chuyên sâu tiếng Việt đã nạp 18/11 (giữ nguyên văn của Vân; thêm 2 cột `third_letters.specialty_vi`, `description`).
2. **Nghề tham khảo:** đã dịch tên tiếng Việt cho 51 nghề (18/11, Claude dịch từ tên Đức / Anh), hiện dạng "Tiếng Việt (Tiếng Đức)". Cần Thầy / team duyệt lại cách dịch.
3. **Câu đồng ý và CRM:** hợp đồng kết nối (`HOP_DONG_KET_NOI.md` mục 2.1) bắt buộc gửi `consent.agreed = true`; Nghị định 13/2023: im lặng không được coi là đồng ý. Cần chốt ở H5 gửi gì cho CRM (hỏi Thầy).
4. **Hộp thư gửi mã** khi lên máy chủ thật (điền SMTP vào `.env`).
5. Loại trường (Uni, HAW…) chỉ hiện cho khách khi ngành đã "Đã kiểm tra" (dữ liệu chưa kiểm tra có ghi chú nội bộ như "Cần tra").

### H3 đã làm (18/11/2026)

Trang khách làm theo 2 mẫu đã chốt (`docs/mau_giao_dien/trang-gioi-thieu.html`, `luong-khach.html`), Blade thuần, ưu tiên điện thoại.
- **Đầu trang chỉ có logo** (tên + khẩu hiệu sửa ở "Giao diện"), **chân trang gọn**: hotline, email, địa chỉ, bản quyền, "Về trang chủ". Bỏ menu đầu trang và các ô chân trang cũ (tên công ty, mô tả, giờ làm việc, link).
- **Trang bắt đầu** (`/`):
  - tiêu đề có phần cuối chữ nghiêng đỏ, 3 lợi ích, nút "Bắt đầu làm bài" (giữ nguồn UTM);
  - bên phải là **"Báo cáo mẫu"**: mã ví dụ (mặc định IS) chọn trong cài đặt, tên nhóm và 2 ngành lấy từ danh mục; admin chọn ảnh banner thì ảnh thay khung này;
  - chữ có `{so_cau}` tự hiện số câu của đề (đang là 36); admin đổi số câu thì chữ tự đổi, không phải sửa tay;
  - 3 khối cũ (Cách làm bài, 6 nhóm, Câu hỏi thường gặp) **mặc định tắt**, admin bật lại được ở "Trang mở đầu".
- **Trang làm bài** (`/lam-bai`):
  - 6 câu / trang (theo cài đặt), chấm 1–5 sao, hiện nhãn khi chọn ("Khá giống tôi"…), thanh tiến độ một dải liền;
  - câu nào cũng bắt buộc: thiếu thì tô đỏ câu thiếu và báo, câu đã chọn vẫn giữ; bấm "Quay lại" không mất câu trả lời; không nhảy qua trang chưa làm;
  - lưu sau mỗi trang, lỡ đóng tab thì bấm "Bắt đầu" lại sẽ làm tiếp đúng chỗ;
  - lượt làm bài giữ trong phiên của trình duyệt, không có mã trên đường link; ghi thời gian chọn mỗi câu (dùng cho cờ "làm quá nhanh").
- **Kết quả sơ lược** (`/ket-qua`): mã 2 chữ, tên 2 nhóm, câu giải thích của cặp, **2 ngành hiện rõ** (ô mới "Kết quả sơ lược hiện rõ mấy ngành" trong "Cách bốc đề & chấm điểm"), các ngành còn lại làm mờ và **không gửi tên thật ra trình duyệt**, nút "Xem báo cáo đầy đủ".
- **Nút "Xem báo cáo đầy đủ"** hiện mở trang "Phần này đang được hoàn thiện"; H4 làm form, OTP, báo cáo.
- Trang làm bài, kết quả: `noindex`. Giới hạn bấm: bắt đầu 10 lần / phút, gửi trang 30 lần / phút.
- Chữ trên trang làm bài và kết quả nằm trong cài đặt (nhóm `result`); trang admin để sửa các chữ này làm cùng "Chữ trên báo cáo & email" ở H4.
- Nút "Xem trang làm bài như khách" trên admin giờ mở trang bắt đầu thật. Bỏ bản mẫu ở `/lam-bai?mau=`.
- Database chạy thử của Vân đã cập nhật chữ mặc định mới (chỉ đổi ô còn đúng chữ mặc định cũ, chữ Vân đã sửa giữ nguyên).
- **Kiểm thử: 107/107 đạt** (thêm `CustomerFlowTest` 7 ca: bắt đầu, bắt buộc từng câu, không nhảy trang, quay lại giữ câu trả lời, kết quả không lộ ngành ẩn, làm tiếp / làm lượt mới ra đề mới).

### Vân chốt 17/11 (chiều)

- **34 tỉnh / thành:** Vân đã kiểm tra, đúng.
- **Ngành gợi ý phải là ngành có thật ở Đức, theo nguồn chính thức**, không lấy từ nguồn không chính thống trên mạng.
  - Hiện **46 ngành** đều "Đề xuất – chưa kiểm định".
  - Mã Holland: 39 ngành lấy từ bài test SIT của HRK (Hội đồng Hiệu trưởng các trường đại học Đức, 2014); 7 ngành chưa có mã.
  - Đức không có một "danh mục ngành của Bộ Giáo dục" chung như Việt Nam (giáo dục đại học do từng bang quản lý).
  - Nguồn chính thức đề xuất: **Hochschulkompass** (HRK, danh sách chương trình được nhà nước công nhận), **studienwahl.de** (Cơ quan Lao động Liên bang và các bang).
- **Giao diện:** căn chỉnh cho dễ nhìn. Đã sửa nút chính (đỏ đúng màu thương hiệu, trước bị hồng nhạt như bị khóa).
- Nút **"Xem trang làm bài như khách"** mở `/lam-bai`. Trên máy chạy thử, trang này hiện bản mẫu luồng khách đã chốt cho tới khi H3 làm xong.

### H2 đã làm (17/11/2026)

- **Bốc đề** (`QuestionPicker`):
  - mỗi nhóm 6 câu "Đang dùng", ngẫu nhiên theo mức ưu tiên (Efraimidis–Spirakis), ít nhất 4 chủ đề;
  - hạn chế lặp câu lần trước trên cùng máy; câu luôn có trong mọi đề (khi bật); trộn thứ tự 36 câu;
  - thiếu câu hoặc thiếu chủ đề thì vẫn ra đề và ghi cảnh báo cho quản trị viên;
  - lưu hạt giống ngẫu nhiên để dựng lại đúng đề.
- **Chấm điểm và phân tích** (`ScoringEngine`): chuyển nguyên bản tham chiếu của Thầy.
  - Điểm trung bình, thứ tự (bằng điểm theo R I A S E C), mã 2 / 3 chữ, khi nào khách thấy chữ thứ 3, mã thay thế.
  - Quan hệ lục giác, độ nhất quán, trục Prediger, kiểu hồ sơ A–D (cần tư vấn riêng khi ≤ 1 nhóm trên mốc), cờ chất lượng bài làm.
- **Gợi ý ngành** (`MajorMatcher`): theo chỉ số C. Thêm 2 công tắc trong "Cách bốc đề & chấm điểm":
  - **"Ưu tiên ngành nền của cặp mã"**: bật mặc định, đúng bản tham chiếu;
  - **"Chỉ gợi ý ngành đã kiểm tra nguồn chính thức"**: tắt mặc định, vì chưa ngành nào được kiểm tra; bật thì khách chỉ thấy ngành có dấu "Đã kiểm tra".
  - Thêm cột `majors.is_verified`, dùng cùng `source_url` (link nguồn). Tư vấn viên vẫn thấy mọi ngành, kèm dấu chưa kiểm tra.
- **Lượt làm bài** (`AssessmentService`):
  - `start()` lưu 36 câu kèm nội dung và phiên bản câu lúc phát đề, phiên bản cài đặt;
  - `finish()` kiểm tra đủ 36 câu và thang 1–5, chấm, lưu kết quả nội bộ và phần khách được thấy (mã 2 chữ hoặc 3 chữ, tối đa 5 ngành, chỉ nhãn chữ);
  - admin sửa cài đặt hay câu hỏi sau đó thì kết quả cũ không đổi.
- **Kiểm thử: 102/102 đạt.**
  - **8/8 ca của Thầy khớp hoàn toàn** bản tham chiếu, gồm cả 5 ngành đầu.
  - Test bốc đề: 20 hạt giống, câu ưu tiên cao được bốc nhiều hơn rõ rệt, không lặp câu, câu luôn có, cảnh báo.
  - Test lượt làm bài: lưu, từ chối câu trả lời sai, dùng cài đặt lúc bắt đầu, 2 công tắc ngành, máy cũ gặp câu mới.

**Việc cần làm tiếp về dữ liệu ngành** (team, trước khi bật "Chỉ gợi ý ngành đã kiểm tra"):
1. Mỗi ngành: tìm trang chính thức trên Hochschulkompass hoặc studienwahl.de, dán link, đánh dấu "Đã kiểm tra". Trang sửa ngành làm ở H6; cần sớm thì mình làm file Excel để điền.
2. Gán mã Holland cho 7 ngành chưa có mã (chuyên gia).

### Vân chốt sau trang mẫu admin (17/11/2026)

- **Phân quyền theo việc thật:**
  - Tư vấn viên chỉ **tra cứu từng khách** để đọc báo cáo nội bộ: không có danh sách, không xem số liệu.
  - Nhân viên chỉ **xem thông tin liên lạc** của khách mới và sửa trang khách xem: không thấy kết quả, không xem số liệu.
  - **Số liệu làm bài, danh sách toàn bộ khách, tải dữ liệu**: chỉ quản trị viên.
- **Không lộ giới hạn:** trang của tư vấn viên, nhân viên không ghi ra họ bị giới hạn gì; trang không có quyền báo "không tìm thấy" (404).
- **Báo cáo nội bộ:** nút "In / PDF" và "Sao chép cho AI" chỉ quản trị viên thấy.
- **Ghi kết quả cuộc gọi:** ở CRM như đã chốt 14/11.
- **Ô giới thiệu trên viger.edu.vn:** giữ chức năng; đoạn mã gửi người làm web chính thu gọn, chỉ quản trị viên thấy.
- **Tra cứu báo cáo khách** và **Khách cần liên lạc** làm ở H4 (cùng báo cáo); **Số liệu làm bài** làm ở H5–H6, chỉ quản trị viên.
- **Danh sách trường** (H6):
  - chỉ tư vấn viên và quản trị viên thấy;
  - ẩn mặc định 20 trường ĐH Hành chính / Đặc thù;
  - file 16 bang bỏ cột thủ hiến, bỏ ký hiệu "[8]";
  - thiết kế mở cho nhiều nước (Quốc gia, Vùng, Loại trường là dữ liệu; nhập Excel có bước ghép cột);
  - "trường có ngành này" chờ team điền file trường ↔ ngành.
- **Thuật toán (cho Thầy chỉnh sau, không cần sửa code):**
  - "ngành nền đứng trước" làm thành công tắc trong "Cách bốc đề & chấm điểm" ở H2;
  - chặn ngành khi nhóm thấp: tăng ô "Mốc xét ngành ngoài cặp";
  - tỉ trọng môn: sửa ở trang Ngành học (H6).

### F2 đã làm (17/11/2026)

- **Menu một chỗ:** `config/admin_menu.php` giữ tên, nhóm, quyền, biểu tượng, câu mô tả, hướng dẫn nút "?" và nhãn "Sắp có" của mọi mục. Đổi tên một mục chỉ sửa một dòng.
- **Tên theo việc làm:**
  - menu: Trang chủ quản trị; nhóm Khách hàng / Bài trắc nghiệm / Trang khách xem / Quản lý hệ thống;
  - trang: Câu hỏi trắc nghiệm; Cách bốc đề & chấm điểm; Ô giới thiệu trên viger.edu.vn; Kho ảnh; Tài khoản nhân sự; Phân quyền…;
  - câu hỏi: tình trạng "Đang dùng / Tạm ngưng / Nháp", mức ưu tiên "Cao / Vừa / Thấp";
  - các ô trong "Cách bốc đề & chấm điểm" viết thành câu hỏi, ô ít dùng gom vào phần "Nâng cao" thu gọn.
- **Phân quyền mới:**
  - 14 quyền viết thành câu "Được…", chia 4 nhóm (có / không có thông tin cá nhân của khách, bài trắc nghiệm, quản lý hệ thống), mỗi quyền có dòng giải thích;
  - trang Phân quyền chia ô tick theo nhóm; quyền tải dữ liệu hiện 🔒, không tick được;
  - mặc định: Tư vấn viên = tra cứu từng khách; Nhân viên = thông tin liên lạc khách mới + sửa trang khách xem;
  - chặn thêm: người không phải quản trị viên không sửa được vai trò của chính mình.
- **Nút "?"** vàng trên mọi trang Filament: hộp "Trang này dùng để làm gì?" với các bước "muốn làm gì → bấm gì". Trang cũ dùng chung nội dung.
- **Trang chủ quản trị:**
  - lời chào; tối đa 6 ô lối tắt, việc dùng được ngay đứng trước;
  - 4 ô số liệu chỉ hiện cho người có quyền.
- **Thanh trên:** "Xem trang làm bài như khách", "Web chính" (quản trị viên, khi đã nhập địa chỉ), "Mở CRM" (khi đã nhập địa chỉ), tên và vai trò.
- **Mục "Sắp có":** hiện trong menu với nhãn, bấm vào mở trang giải thích phần đó làm gì, giai đoạn nào xong. Chỉ hiện cho người có quyền tương ứng.
- **404 thay 403** trong khu quản trị.
- **Tài khoản nhân sự:** nút Khóa / Mở khóa ngay trên bảng (kiểm tra quyền phía máy chủ, không chỉ ẩn nút).
- **Ô giới thiệu:** đoạn mã nhúng thu gọn vào "Dành cho người làm web chính", chỉ quản trị viên thấy.
- **Đã xóa** `settings.json`, `form_fields.json` cũ (D-17). Đưa vào git 2 file trường / bang và trang mẫu admin.
- **Kiểm thử: 80/80 đạt.** Test mới:
  - menu và ô lối tắt từng vai trò; trang "Sắp có";
  - nút "?" trên các trang; thứ tự ô lối tắt;
  - trang của vai trò bị giới hạn không ghi giới hạn; 404;
  - Phân quyền chia nhóm và không cấp được quyền tải dữ liệu; khóa / mở khóa tài khoản; chặn tự nâng quyền.

### F đã làm (17/11/2026)

Vân chốt: trang quản trị dùng **Filament** (đỡ làm từ đầu, dễ sửa, dễ mở rộng); trang khách vẫn Blade thuần theo mẫu. **Bỏ vai trò Marketing**: marketing là Nhân viên.

- **Cài đặt:**
  - Filament v5.11 (miễn phí, MIT), không cài plugin nào;
  - `openspout` hạ xuống 4.x vì Filament cần bản này (chưa có code nào dùng);
  - `composer audit` không báo lỗ hổng nào.
- **Khu quản trị `/admin`:**
  - logo, màu đỏ, tên đơn vị lấy từ cài đặt; giao diện tiếng Việt; `noindex`;
  - đăng nhập tại `/admin/dang-nhap` bằng tên đăng nhập hoặc email; sai 5 lần / phút thì tạm khóa;
  - tài khoản bị khóa giữa phiên sẽ bị đăng xuất.
- **Trang mới:**
  - **Bảng điều khiển:** 4 ô số liệu (câu đã duyệt và nhóm nào thiếu câu, câu tạm ngưng / nháp, lượt làm bài, phiên bản cài đặt);
  - **Thư viện câu hỏi:**
    - thẻ theo 6 nhóm, tìm kiếm, lọc chủ đề / loại / trạng thái, duyệt / tạm ngưng nhiều câu, thùng rác và khôi phục;
    - sửa chữ thì phiên bản câu tăng; đổi trọng số phải ghi lý do (lưu người đổi);
    - câu mới tự đặt mã R051…, ở trạng thái Nháp; không ai xóa hẳn được;
  - **Cài đặt bài test:** chuyển sang Filament, giữ phiên bản, ghi ô nào đổi, nút "Quay lại bản này";
  - **Tài khoản:** thêm / sửa / khóa, đặt lại mật khẩu (ít nhất 10 ký tự, có chữ và số);
    - không ai tự đổi vai trò, tự khóa hay tự xóa mình;
    - chỉ quản trị viên sửa được quản trị viên;
  - **Vai trò & quyền:** thêm vai trò, tick quyền;
    - vai trò Quản trị viên khóa cứng;
    - vai trò lõi hoặc còn tài khoản thì không xóa được;
    - quyền "xuất dữ liệu" không cấp được cho ai ngoài quản trị viên.
- **Trang cũ giữ lại**, mở từ menu nhóm "Nội dung trang": Trang giới thiệu, Nhúng vào web chính, Giao diện & chân trang, Thư viện ảnh. Có nút "Về trang quản trị". Chuyển hẳn sang Filament ở H6.
- **Đã gỡ:** đăng nhập cũ, bảng điều khiển cũ, trang "Đang xây dựng", controller Cài đặt bài test cũ.
- **Vai trò:** còn 3 (Quản trị viên, Tư vấn viên, Nhân viên); Nhân viên có thêm quyền nội dung trang.
  - Biến `.env` mới: `HOLLAND_STAFF_*`. Biến cũ `HOLLAND_EDITOR_*` vẫn đọc được.
- **Thêm `lang/vi/validation.php`:** trước đây dự án không có file này, nên form Filament sẽ hiện mã kiểu `validation.required`. Nay mọi lỗi nhập đều bằng tiếng Việt.
- **Kiểm thử:** **71/71 đạt**. Gồm test mới: thư viện câu hỏi, tài khoản & vai trò, đăng nhập Filament, các trang mở được, thông báo lỗi tiếng Việt.

**Lưu ý khi cài trên máy khác / máy chủ:**
- `composer install` sẽ tự chạy `php artisan filament:upgrade`, chép CSS / JS của Filament vào `public/` (thư mục này không đưa lên git).
- Mục "Hồ sơ của tôi" (tự đổi mật khẩu) và xác thực 2 lớp: chưa bật. Đề xuất bật xác thực 2 lớp cho quản trị viên trước khi lên máy chủ thật.

### H1 đã làm (17/11/2026)

- **Module:** model, `TestConfig`, controller, request chuyển vào `app/Modules/{Assessment,Respondent,Access}`. Mỗi class có chú thích *Làm gì / Ai gọi / Dữ liệu*; mỗi module có `README.md`.
- **6 migration:**
  - danh mục (6 nhóm, 15 cặp, chữ thứ ba, mã riêng, cụm ngành, môn, ngành, nghề, môi trường, liên kết);
  - thư viện câu hỏi;
  - phiên bản cài đặt;
  - `form_fields` (có `crm_key`, kiểu `radio`);
  - lượt làm bài;
  - `roles` / `permissions` / `role_permission`.
- **Phân quyền bằng dữ liệu:**
  - 12 mã quyền; 4 vai trò: Quản trị viên (mọi quyền), Tư vấn viên (tổng quan + lượt làm bài), Nhân viên (tổng quan), Marketing (tổng quan + nội dung trang; xóa được);
  - mỗi mục menu ghi một mã quyền; gõ thẳng địa chỉ bị chặn 403; vai trò bị tắt thì không vào được admin;
  - chỉ quản trị viên xuất dữ liệu.
- **Seeder (chạy lại không trùng, không ghi đè chỗ admin đã sửa, câu / ngành đã cho vào thùng rác không bị nạp lại):**
  - `RoleSeeder`;
  - `CatalogSeeder`: 6 nhóm, 15 cặp (giữ thứ tự ngành), 6 chữ thứ ba, 7 cụm ngành, 9 môn, 46 ngành, 51 nghề, 14 môi trường, 53 liên kết;
  - `QuestionBankSeeder`: 300 câu, 60 chủ đề, đã duyệt, trọng số A/B/C = 5/3/1, "Em/em" → "Tôi/tôi";
  - `TestConfigSeeder`: phiên bản 1;
  - `FormFieldSeeder`: 7 ô, `crm_key` theo từ điển.
- **Trang Cài đặt bài test** (`/admin/test-settings`):
  - thêm nhóm "Trang làm bài": số câu mỗi trang (6), chữ của 5 mức sao;
  - lưu theo phiên bản, ghi rõ ô nào đổi, quay lại bản cũ.
- **Kiểm thử:**
  - `migrate:fresh --seed` rồi `db:seed` lần 2 trên SQLite: không lỗi, số lượng giữ nguyên;
  - 14 test mới (nạp dữ liệu, cài đặt, phân quyền); tổng **55/55 đạt**.

**Câu hỏi / việc chờ (cập nhật 17/11):**
1. **Tỉ trọng môn theo cụm ngành** (`CatalogSeeder::SUBJECT_WEIGHTS`) là đề xuất ban đầu của mình, dựa trên cột môn nền. Cần Thầy duyệt; sửa được ở admin từ H6.
2. ~~34 tỉnh / thành~~: Vân đã kiểm tra, đúng.
3. Mã `KHAC` cần thêm vào `TU_DIEN_DU_LIEU_CHUNG.xlsx` / `lua_chon_chuan.json` bên CRM.
4. ~~File cũ `settings.json`, `form_fields.json`~~: đã xóa (Vân đồng ý).
5. Hỏi Thầy:
   - Khoa học sức khỏe khớp 5/18 vẫn đứng thứ 2 (ngành nền cặp IS);
   - Kinh tế học (IEC) vẫn được gợi ý cho HS01 dù nhóm E thấp.
   - Cả hai chỉnh được bằng cài đặt, không gấp.
6. Team: danh sách **trường ↔ ngành** Viger hay gửi hồ sơ; xác định hệ đào tạo cho **22 ngành** đang ghi "Cần tra".
7. Trước khi lên máy chủ thật: bật xác thực 2 lớp cho quản trị viên.

**Việc Vân cần làm:**
- Trên máy Vân chạy `composer install`, rồi `php artisan migrate:fresh --seed` một lần (bảng mới, dữ liệu, 3 vai trò). Sau đó vào `/admin` xem thử.
- Trong `.env` nên đổi `HOLLAND_EDITOR_*` thành `HOLLAND_STAFF_*` (không đổi vẫn chạy).

## Làm lại theo VIGER EDU Career Map (từ 11/11/2026, nhánh `feature/holland-v3`)

Sau buổi họp 11/11, Thầy chốt hướng mới: thư viện 300 câu, rút ngẫu nhiên có trọng số, điểm trung bình cộng, mã 2/3 chữ, 120 mã có thứ tự, ngành – nghề Đức, báo cáo khách / nội bộ. Bản cũ (FIT, bộ câu kiểu Google Form, 75 ngành) **đã gỡ**; xem `CLAUDE.md` mục 2, 4, 7.

**Quyết định của Vân (11/11/2026):**
- Rút đề **ngẫu nhiên có trọng số từ 300 câu** cho mọi lượt (không dùng đề cố định); 300 câu nạp ở trạng thái **đã duyệt** (Thầy đã chọn).
- **Không có nút "Chưa biết"**: Likert 1–5, câu nào cũng bắt buộc.
- **Không có nút đặt lịch**: gửi kết quả qua email, sau nối CRM. Hai phiên bản báo cáo: khách (external) và nội bộ (internal).
- Thứ tự: **điền thông tin trước → làm bài → báo cáo** (theo bản mẫu của Thầy).
- Chưa có dữ liệu khách thật → **làm sạch** migration cũ một lần; từ khi lên máy chủ thật mới áp dụng "chỉ thêm".
- Bảng trường / chương trình đại học: để phiên bản sau.

| Giai đoạn | Trạng thái |
|---|---|
| 0. Nhánh mới, gỡ phần nghiệp vụ cũ, giữ hạ tầng, sửa CLAUDE.md | ✅ Xong |
| 1. Bảng dữ liệu + nạp 300 câu, danh mục, cài đặt, trường thông tin; trang Cài đặt bài test | Chờ duyệt kế hoạch |
| 2. Bộ máy rút đề, chấm, phân tích, gợi ý ngành | Chưa làm |
| 3. Luồng khách: thông tin → làm bài → báo cáo → email | Chưa làm |
| 4. Admin thư viện câu hỏi, thống kê, Excel | Chưa làm |
| 5. Admin danh mục, trường thông tin, chú thích | Chưa làm |
| 6. Báo cáo nội bộ, vai trò tư vấn viên, lượt làm bài | Chưa làm |
| 7. API, CRM, tài khoản, sao lưu, tài liệu, nghiệm thu | Chưa làm |

**GĐ0 đã làm:** gỡ thuật toán FIT, bộ câu, form Phần A, trang làm bài / kết quả / email cũ, 75 ngành, dữ liệu `database/seeders/data/holland/` (giữ `regions.json` → `database/seeders/data/shared/`); giữ đăng nhập, khung admin, nội dung trang giới thiệu, khối nhúng, thư viện ảnh, lịch sử + quay lại bản cũ (`HistoryController`), chặn lệnh xóa database. Menu admin theo cấu trúc mới (mục chưa làm hiện "Đang xây dựng – giai đoạn X"). Nút "Bắt đầu" tạm mở trang "đang cập nhật". `robots.txt` chặn `/admin`. 41 test đạt.

**Việc Vân cần làm sau GĐ0:** trên máy Vân, bật Laragon (Start All) rồi chạy `php artisan migrate:fresh --seed` **một lần** (database máy Vân còn bảng cũ). Lệnh này bị chặn trên máy chủ thật.

---

# Lịch sử bản cũ (trước 11/11/2026, đã thay)
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

**Con số hiện tại:** 76 câu hỏi (22 câu thông tin khách, 12 đang bật + 54 câu Holland mẫu) · 1 bộ câu `thpt` · 75 ngành/nghề · **160 test đều đạt** · nhánh `dev`, **chưa push**.

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
8. **Dữ liệu ngành có mã Holland trùng hệt nhau** (kiểm tra 100 hồ sơ, 06/11): 8 ngành đại học cùng mã IRC, 6 ngành ICR, 5 ngành ISE, 3 ngành RIE. Cùng mã thì cùng điểm phù hợp, công thức xếp theo mã ngành nên **Tin sinh học (N019), Hóa sinh (N051), Công nghệ thực phẩm (N053) không bao giờ được gợi ý**, kể cả cho học sinh hợp nhất. Đề nghị Thầy / Ý bổ sung mã thứ 2 – 3 hoặc chỉnh mã các ngành này trong danh mục ngành (không phải sửa code).

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
- Migration chỉ thêm: bảng `media`. **153 test đều đạt** (18 test mới). Đã chụp ảnh kiểm tra trang giới thiệu ở 1366px và 496px, khối nhúng.

**Chưa làm / để sau:** SEO + ô "Mã nhúng chatbot" thuộc trang Cài đặt (giai đoạn 7); câu báo lỗi cho khách ("Vui lòng điền…") vẫn trong code – đưa vào admin ở giai đoạn 7 cùng Cài đặt.

### Bảng nghiệm thu – việc đã làm được đến giai đoạn 3c
| Vai trò | # | Việc | Trạng thái |
|---|---|---|---|
| Sếp | 1 | Đổi số hotline, địa chỉ, logo | ✅ Trang "Giao diện & chân trang" (phía web Holland) |
| Sếp | 4 | Sửa câu hỏi Holland, thêm câu "Trường chuyên?" | ✅ |
| Sếp | 5 | Đổi chữ trên trang kết quả Holland | ✅ Có xem trước |
| Sếp | 7 | Tài khoản marketing chỉ thấy phần của mình | Một phần: vai trò đã chặn đúng; tạo tài khoản trên web ở giai đoạn 6 |
| Marketing | 2 | Thay banner | ✅ Phía web Holland (ảnh banner máy tính + điện thoại); hẹn giờ banner là việc của web chính |

## Đợt "Dễ dùng" A + B + C + D (xong 06/11/2026)

Vân dùng thử thấy admin còn khó với người không làm IT (sửa bằng cột ô trống, nhiều từ kỹ thuật, sửa câu phải vào ra từng trang). Đã làm:

- **A. Sửa trực tiếp trên trang** (`/admin/sua-trang/…`): menu "Trang giới thiệu", "Trang kết quả", "Email gửi khách" mở đúng trang khách thấy với thanh đen trên cùng. Chữ sửa được có **viền chấm** → bấm vào gõ; ảnh → bấm để chọn từ thư viện; đoạn có định dạng có thanh nhỏ (đậm, nghiêng, link, danh sách); mỗi khối có **Lên / Xuống / Ẩn khối / Thêm-bớt dòng**. Sửa được cả tên + mô tả 6 nhóm, câu hỏi thường gặp, đầu trang, chân trang (hotline, Zalo, email, địa chỉ – chỉ quản trị viên). "Lưu thay đổi" ghi lịch sử như cũ (quay lại được), nhắc khi rời trang chưa lưu. Trang kết quả dùng học sinh mẫu; hiện cả 2 câu nhắc để sửa. Form cũ vẫn còn ("Sửa dạng biểu mẫu").
- **B. Câu hỏi như Google Form**: trang bộ câu và form thông tin khách thành **danh sách thẻ** – bấm thẻ mở form ngay tại chỗ, **sửa đến đâu tự lưu đến đó** (báo "Đã lưu ✓" / dòng đỏ nếu thiếu), **thêm nhanh** (gõ câu + Enter), **Nhân đôi**, **Xóa** (vào thùng rác), kéo thả tự lưu. Lịch sử gộp các lần tự lưu liên tiếp (3 phút) thành một dòng.
- **C. Form gọn**: chỉ hiện ô cần (câu hỏi, cách trả lời, nhóm / lựa chọn, bắt buộc, hiện); phần kỹ thuật (mục lưu hồ sơ khách, điều kiện, riêng tư, ghi chú…) vào "Tùy chọn nâng cao". Câu mới **tự tạo mục lưu trong hồ sơ khách** theo câu hỏi (không cần biết "mã trường"). Tên nhóm hiện chữ: "Kỹ thuật (R)".
- **D. Link**: gõ `viger.edu.vn/…` tự thành `https://…` (menu, mạng xã hội, nút khối, CKEditor); câu báo lỗi dễ hiểu hơn.
- Sửa lỗi: phần tử có `hidden` vẫn hiện do CSS (`[hidden] { display: none !important }`).
- **153 test đều đạt** (7 test mới cho thẻ câu hỏi, thêm nhanh, tự lưu, sửa trên trang, quyền).

**Nên làm tiếp:** cho Thầy thử 3 việc (đổi tiêu đề trang giới thiệu, thêm câu hỏi, đổi hotline) không hướng dẫn trước, ghi lại chỗ vướng vào `docs/NGHIEM_THU.md`.

## Đợt 2 (06/11/2026): giao diện admin đỏ – vàng, kiểm tra 100 hồ sơ, ~100 tình huống

- **Bỏ học sinh mẫu khỏi giao diện:** trang Cấu hình chấm điểm tự nhập % để chạy thử; xem trước / sửa trên trang hiện "[Tên khách]". Test tự động theo `test_cases.json` vẫn giữ (CLAUDE.md mục 4).
- **100 hồ sơ giả lập** (`ScoringHundredProfilesTest`, cố định): mã đúng 3 nhóm cao nhất, top 5 / top 3 sắp đúng, nhãn đúng ngưỡng, ngành đầu luôn chung chữ với mã học sinh (100/100), hồ sơ có nhóm nổi trội luôn được gợi ý ngành chứa nhóm đó (30/30), 67 / 75 ngành – nghề có lúc được gợi ý. Phát hiện: dữ liệu ngành có nhiều mã trùng (câu hỏi 8 ở trên).
  - **Đã chỉnh:** câu nhắc "bằng điểm" trước hiện cho 70/100 học sinh (bài 54 câu rất hay bằng điểm) → chỉ hiện khi nhóm thứ 3 và 4 bằng nhau (chữ cuối của mã chưa chắc). Cờ `has_tie` gửi CRM giữ nguyên. Công thức không đổi.
- **Giao diện admin theo ảnh mẫu, tông đỏ – vàng của web chính** (đỏ `#c1121f`, đỏ đậm `#780000`, vàng `#ffb703`): menu trái nền tối có biểu tượng + khung người dùng, thanh trên màu đỏ, tiêu đề trang kèm đường dẫn (Trang chủ › nhóm › trang), nút "?" vàng, hộp nội dung viền đỏ trên. Trang danh sách dạng bảng: tiêu đề bảng đỏ, thanh công cụ (tìm / lọc / "+ Thêm mới" vàng), **công tắc Hiển thị**, nút biểu tượng xem / sửa (vàng) / xóa (đỏ) – áp dụng cho Bộ câu hỏi, Lượt làm bài (lọc theo mã, bộ câu, thời gian), Các khối nội dung. Bảng điều khiển: 6 ô "việc hay làm" có biểu tượng, lọc theo vai trò. Soạn câu hỏi giữ dạng thẻ Google Form.
- **Kiểm thử ~100 tình huống** (`QualityScenariosTest`): quản trị viên mở 34 trang không lỗi; marketing đúng quyền ở 34 trang; 10 đường dẫn khách / sai; 18 tình huống khách nhập sai (bỏ trống, email / SĐT / ngày sai, sửa lựa chọn, quá dài, thiếu câu, SĐT +84 / dấu chấm / dấu cách vẫn nhận, gửi dồn bị chặn); 12 tình huống mã độc (`<script>`, link `javascript:`, file giả ảnh, khóa lạ, chưa đăng nhập). Không phát hiện lỗi mới ngoài 2 lỗi đã sửa trong đợt (phần tử ẩn vẫn hiện, câu nhắc bằng điểm).
- **160 test đều đạt.** Chưa kiểm được bằng tay các thao tác cần chuột thật (kéo thả, tự lưu, chọn ảnh) – Vân / Thầy thử giúp.

## Tiếp theo: Giai đoạn 4 – Kết nối CRM

Danh mục ngành từ CRM (dự phòng file `majors.json`), gửi khách sang CRM thật qua hàng đợi + thử lại + lịch chạy, trang "Hàng đợi gửi CRM" (gửi lại), trang "Kết nối CRM & chatbot" (bật/tắt, địa chỉ, mã kết nối, nút "Kiểm tra kết nối", ô mã nhúng chatbot). Test bằng `Http::fake()`.
