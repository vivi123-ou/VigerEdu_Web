# Module Assessment (bài trắc nghiệm Holland)

**Làm gì:** giữ thư viện 300 câu, danh mục Career Map (6 nhóm, 15 cặp, 6 hướng chuyên sâu, ngành, nghề, môi trường) và cài đặt bài test. Bộ máy bốc đề, chấm điểm, phân tích, gợi ý ngành nằm ở `Services/` (H2).

**Bảng:**
- `question_facets`, `questions`, `question_weight_audits`: thư viện câu hỏi;
- `riasec_types`, `riasec_pairs`, `pair_major`, `third_letters`, `code_overrides`: danh mục mã;
- `major_groups`, `subjects`, `majors`, `occupations`, `environments`, `major_occupation`, `environment_occupation`: ngành và nghề;
- `test_config_versions`: cài đặt theo phiên bản;
- `test_sessions`, `session_questions`, `responses`: lượt làm bài (dùng từ H2–H3).

**Code:**
- `Models/`;
- `Support/TestConfig.php`: đọc, ghi, kiểm tra cài đặt. Mọi con số (6 câu, 4,2, 0,25, trọng số…) lấy qua `TestConfig::get()`, không viết cứng;
- `Policies/QuestionPolicy.php`: quyền `questions.manage`; không ai xóa hẳn câu hỏi;
- `Filament/Resources/Questions`: trang **Câu hỏi trắc nghiệm** (`/admin/questions`):
  - lọc theo nhóm, chủ đề, mức ưu tiên (Cao / Vừa / Thấp), tình trạng (Đang dùng / Tạm ngưng / Nháp); dùng / tạm ngưng nhiều câu; thùng rác;
  - sửa chữ thì phiên bản câu tăng (1.0 → 1.1);
  - đổi con số ưu tiên (trọng số) phải ghi lý do (bảng `question_weight_audits`);
  - câu mới tự đặt mã (R051…) và ở trạng thái Nháp;
- `Filament/Pages/TestSettings.php`: trang **Cách bốc đề & chấm điểm** (`/admin/test-settings`, quyền `test_settings.manage`), các ô ít dùng nằm trong phần "Nâng cao" thu gọn. Mỗi lần lưu là một phiên bản; có lịch sử và nút "Quay lại bản này".

**Bộ máy (H2, `Services/`):**
- `QuestionPicker`: bốc đề. Mỗi nhóm 6 câu "Đang dùng", ngẫu nhiên theo mức ưu tiên (Efraimidis–Spirakis), ít nhất 4 chủ đề, hạn chế lặp câu lần trước trên cùng máy, câu luôn có trong mọi đề, trộn thứ tự; thiếu câu / thiếu chủ đề thì vẫn ra đề và ghi cảnh báo. Có hạt giống ngẫu nhiên để dựng lại đúng đề.
- `ScoringEngine`: chấm và phân tích, giữ nguyên kết quả bản tham chiếu `docs/holland_v3/HollandEngine.php`: điểm trung bình, thứ tự, mã 2 / 3 chữ, chữ thứ 3 cho khách, mã thay thế, quan hệ lục giác, độ nhất quán, trục Prediger, kiểu hồ sơ A–D, cờ chất lượng.
- `MajorMatcher`: gợi ý ngành theo chỉ số C. Có 2 công tắc trong cài đặt: "Ưu tiên ngành nền của cặp mã" và "Chỉ gợi ý ngành đã kiểm tra nguồn chính thức" (cột `majors.is_verified`, `source_url`).
- `AssessmentService`: `start()` phát đề (lưu nội dung, phiên bản câu, phiên bản cài đặt); `finish()` kiểm tra câu trả lời, chấm, lưu kết quả bản nội bộ và phần khách được thấy. Kết quả cũ không đổi khi admin sửa sau.

**Trang khách (H3, `Http/`):** Blade thuần, CSS `public/css/khach.css`, JS `public/js/khach.js`.
- `Http/Controllers/QuizController.php`:
  - `start` (POST `/lam-bai/bat-dau`, giới hạn 10 lần / phút): phát đề, ghi nguồn UTM, đặt cookie máy `holland_device` (chỉ lưu dạng băm) để hạn chế lặp câu; đang làm dở thì làm tiếp, không phát đề mới;
  - `show` (GET `/lam-bai`): mỗi trang `items_per_page` câu, chấm sao 1–5; không cho nhảy qua trang chưa làm;
  - `submit` (POST `/lam-bai`, 30 lần / phút): lưu câu trả lời từng trang; thiếu câu thì quay lại đúng trang và tô đỏ câu thiếu; đủ 36 câu thì chấm và sang kết quả;
  - `result` (GET `/ket-qua`): mã 2 chữ, tên 2 nhóm, lời giải thích của cặp, `preview_majors` ngành hiện rõ, các ngành còn lại làm mờ (không gửi tên ngành thật ra trình duyệt).
- Lượt làm bài giữ trong phiên đăng nhập của trình duyệt (`holland.test`), không đưa mã vào đường link.
- `Http/Requests/SubmitAnswersRequest.php`: kiểm tra dữ liệu gửi lên; `AssessmentService::saveAnswers()` kiểm tra lại câu có thuộc đề này không.
- `Support/SampleReport.php`: khung "Báo cáo mẫu" ở trang bắt đầu (mã ví dụ lấy từ cài đặt `intro_sample_code`).
- Chữ trên trang làm bài và trang kết quả: cài đặt nhóm `result` (`quiz_*`, `result_*`). Chữ có `{so_cau}` tự hiện số câu theo cài đặt.

**Dữ liệu ban đầu:** `CatalogSeeder`, `QuestionBankSeeder` (từ `question_bank_300.csv`, nạp ở trạng thái đã duyệt, đổi "Em" thành "Tôi"), `TestConfigSeeder`. Chạy lại không trùng, không ghi đè chỗ admin đã sửa.

**Test:** `tests/Feature/Assessment/`: `ReferenceCasesTest` (8 ca của Thầy), `QuestionPickerTest`, `AssessmentServiceTest`, `CustomerFlowTest` (luồng khách H3), `SeedDataTest`, `TestSettingsTest`, `QuestionLibraryTest`.
