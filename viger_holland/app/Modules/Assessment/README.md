# Module Assessment (bài trắc nghiệm Holland)

**Làm gì:** giữ thư viện 300 câu, danh mục Career Map (6 nhóm, 15 cặp, 6 hướng chuyên sâu, ngành, nghề, môi trường) và cài đặt bài test. Từ H2 sẽ có thêm phần rút đề, chấm điểm, phân tích, gợi ý ngành (`Services/`).

**Bảng:**
- `question_facets`, `questions`, `question_weight_audits`: thư viện câu hỏi;
- `riasec_types`, `riasec_pairs`, `pair_major`, `third_letters`, `code_overrides`: danh mục mã;
- `major_groups`, `subjects`, `majors`, `occupations`, `environments`, `major_occupation`, `environment_occupation`: ngành và nghề;
- `test_config_versions`: cài đặt theo phiên bản;
- `test_sessions`, `session_questions`, `responses`: lượt làm bài (dùng từ H2–H3).

**Code:** `Models/`, `Support/TestConfig.php` (đọc và ghi cài đặt). Mọi con số (6 câu, 4,2, 0,25, trọng số…) lấy qua `TestConfig::get()`, không viết cứng.

**Route:** `admin/test-settings` (`admin.test-settings.*`, quyền `test_settings.manage`).

**Dữ liệu ban đầu:** `CatalogSeeder`, `QuestionBankSeeder` (từ `question_bank_300.csv`, nạp ở trạng thái đã duyệt, đổi "Em" thành "Tôi"), `TestConfigSeeder`. Chạy lại không trùng, không ghi đè chỗ admin đã sửa.

**Test:** `tests/Feature/Assessment/SeedDataTest.php`, `TestSettingsTest.php`.
