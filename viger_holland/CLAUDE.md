# CLAUDE.md – VigerEdu_Holland (web Test Holland, `holland.viger.edu.vn`)

> File này Claude Code tự đọc mỗi phiên. **Đặc tả hiện hành:** `docs/PROMPT_3_CAP_NHAT_HOLLAND.md` + các quyết định ở mục 4 dưới đây (ưu tiên hơn PROMPT 3 khi khác nhau). `docs/PROMPT_2_HOLLAND.md` là bản cũ, chỉ tham khảo phần hạ tầng.
> **Tiến độ:** `docs/TIEN_DO.md`. Đọc đầu mỗi phiên và cập nhật cuối mỗi phiên.

## 1. Tôi là ai, dự án là gì

- Tôi là **Vân (Vivi)**, Team Công nghệ, thực tập tại **Viger-Edu** (tư vấn du học Đức). Giao tiếp bằng **tiếng Việt**.
- Hệ thống gồm **3 platform độc lập + 1 chatbot**, nối với nhau **chỉ qua API** (`docs/HOP_DONG_KET_NOI.md`):
  1. `viger.edu.vn`: web chính, Tuấn làm.
  2. **`holland.viger.edu.vn`: repo này.** Tôi làm. Khách điền thông tin, làm bài Holland, xem báo cáo; dữ liệu gửi sang CRM.
  3. `crm.viger.edu.vn`: CRM, repo riêng, **chưa có** → mọi kết nối CRM chạy được ở **chế độ dự phòng**, test bằng `Http::fake()`.
  4. AI chatbot (Python, bạn Ý): gọi API repo này; nhận báo cáo dạng JSON.
- Yêu cầu của Thầy (không làm IT): mỗi platform gỡ / thay được; **mọi thứ khách thấy và mọi danh mục sửa được trên admin** (phần mềm "mở"); dữ liệu khách tập trung ở CRM, bảo mật; **mỗi trang admin có chú thích** ("Trang này làm gì?", nút "?").

## 2. Sản phẩm: VIGER EDU Career Map (bản làm lại từ 11/11/2026)

- **Luồng khách** (điện thoại trước): giới thiệu → **điền thông tin** → **làm bài 36 câu** (1 câu / màn hình, thang Likert 1–5, **không có nút "Chưa biết"**, câu nào cũng bắt buộc) → **báo cáo cho khách** → email kết quả. **Không có nút đặt lịch** (tư vấn viên liên hệ qua CRM).
- **Hai phiên bản báo cáo** từ cùng một kết quả:
  - *External (khách)*: biểu đồ 6 nhóm, **mã 2 chữ**; chữ thứ 3 chỉ đọc chi tiết khi đủ điều kiện; 3–5 ngành gợi ý với nhãn chữ (không %), nghề tham khảo, bước tiếp theo. Không hứa đậu. `noindex`.
  - *Internal (tư vấn viên / quản trị, phải đăng nhập)*: đủ 6 điểm, mã 3 chữ, mã thay thế, quan hệ lục giác, độ tin cậy, 36 câu đã làm, thông tin khách, JSON cho AI.
- **Admin**: thư viện câu hỏi, cài đặt bài test, danh mục (6 nhóm, 15 cặp, 120 mã, ngành, nghề, môi trường), trường thông tin khách, lượt làm bài, nội dung trang, kết nối, tài khoản. Vai trò: quản trị viên, tư vấn viên, marketing.

## 3. Công nghệ và quy tắc

- **Laravel 13, PHP ≥ 8.3**, MySQL (production) / SQLite (test), Blade + CSS/JS thuần (không Tailwind, không SPA).
- Package ngoài Laravel: **chỉ `openspout/openspout`**. Muốn thêm package khác thì **hỏi tôi trước**.
- Git: nhánh `feature/holland-v3` (tách từ `dev`), commit theo giai đoạn, message tiếng Việt. **Không push, không merge.** Không commit `.env`.
- Giao diện và thông báo **tiếng Việt**. Class, bảng, cột **tiếng Anh**.
- Tên trường thông tin khách / khóa gửi CRM: đúng `docs/TU_DIEN_DU_LIEU_CHUNG.xlsx` và `docs/lua_chon_chuan.json`.
- **Nguyên tắc "mở"**: không ghi cứng chữ, hình, lựa chọn, ngưỡng, tên miền trong code/view; lấy từ bảng cài đặt / danh mục admin.
- **Dễ dùng cho người không làm IT**: `docs/TIEU_CHI_DE_DUNG_VA_NGHIEM_THU.md` (nút "?", xem trước, lịch sử, thùng rác, ≤ 3 lần bấm…).
- Bảo mật:
  - FormRequest cho mọi input, `{{ }}` trong Blade, CSRF, throttle form công khai (5/phút) và API (60/phút);
  - `result_token` là UUID; token API lưu dạng hash, so sánh `hash_equals`;
  - **không ghi SĐT, email, họ tên vào log** (cần thì che: `09xx xxx 678`);
  - `/admin` và mọi trang có dữ liệu khách: bắt buộc đăng nhập + `noindex, nofollow`; `robots.txt` chặn `/admin`;
  - **migration**: bản v3 được làm sạch một lần khi chưa có dữ liệu thật (Vân duyệt 11/11/2026). **Từ khi lên máy chủ thật: chỉ thêm bảng / cột cho phép rỗng**, không xóa, không đổi tên. Chặn `migrate:fresh` / `reset` / `db:wipe` khi production.
- Tài liệu chỉ viết: `README.md`, `docs/API_HOLLAND.md`, `docs/THAY_THUAT_TOAN.md`, `docs/SO_TAY_SU_DUNG.md`, `docs/TIEN_DO.md`, `docs/NGHIEM_THU.md`.
- Chưa rõ hoặc thấy mâu thuẫn: **hỏi tôi**, đừng đoán.

## 4. Thuật toán và đề thi (theo tài liệu của Thầy, chốt 11/11/2026)

**Rút đề – thư viện 300 câu, ngẫu nhiên có trọng số và ràng buộc** (`docs/CẤU TRÚC THƯ VIỆN CÂU HỎI TRẮC NGHIỆM HOLLAND.docx`, `docs/VIGER_Holland_QuestionBank_Programming_Guide_V1.docx`):
1. Thư viện 6 nhóm × 50 câu, mỗi nhóm 10 chủ đề (facet). Loại A/B/C, trọng số chọn mặc định 5/3/1. **Thầy đã chọn bộ 300 câu → nạp ở trạng thái đã duyệt**; admin tạm ngưng / duyệt từng câu.
2. Mỗi lượt: mỗi nhóm lấy câu neo (nếu bật) rồi rút thêm cho đủ `items_per_group` (6) câu theo trọng số, không hoàn lại (Efraimidis–Spirakis: key = −ln(u)/w, lấy k nhỏ nhất). Chỉ rút câu đã duyệt.
3. Ràng buộc: ≥ `min_distinct_facets` (4) chủ đề khác nhau / nhóm; hạn chế lặp câu của lượt trước trên cùng máy (cookie, không dùng SĐT); tối đa 100 lần thử, không đạt thì nới ràng buộc và ghi cảnh báo.
4. Trộn 36 câu; lưu mã câu, phiên bản câu, thứ tự hiện, phiên bản cài đặt. **Trọng số chỉ để chọn câu, không dùng tính điểm.**

**Chấm và phân tích** (`docs/holland_v3/HollandEngine.php` là bản tham chiếu; 8 ca trong `docs/holland_v3/cases.json`):
1. Điểm nhóm = **trung bình cộng** các câu của nhóm (1–5). Không có "Chưa biết".
2. Xếp hạng: điểm giảm dần; bằng nhau theo thứ tự R, I, A, S, E, C.
3. Mã 2 chữ luôn có. Chữ thứ 3 chỉ đọc chi tiết cho khách khi điểm nhóm 3 **> 4,2** và đủ số câu (tương đương ≥ 26/30). Nội bộ luôn thấy đủ.
4. Mã thay thế khi các vị trí 1–2, 2–3, 3–4 cách nhau ≤ 0,25, hoặc nhóm 3 gần ngưỡng.
5. Quan hệ lục giác: liền kề / cách một / đối kháng; độ nhất quán Cao / Trung bình / Thấp.
6. Gợi ý ngành: chỉ số C (Brown & Gore, 0–18) theo mã có thứ tự (ISA ≠ SIA); nhãn ≥ 15 "Liên quan nhiều", ≥ 11 "Liên quan", ≥ 7 "Có thể khám phá"; **không hiện %**.
7. Mọi con số (6 câu, 4,2, 0,25, trọng số, nhãn, số ngành…) đọc từ cài đặt admin, không viết cứng.

## 5. Dữ liệu có sẵn

- `docs/VIGER_Holland_QuestionBank_300_V1.xlsx` / `.csv`: 300 câu, 60 chủ đề, cấu hình rút đề.
- `docs/VIGER_RIASEC_120_codes.xlsx`, `docs/VIGER_CareerMap_Layer4_Germany_V1.xlsx`, `docs/Sơ đồ kiến trúc ứng dụng VIGER EDU Career Map-chitiet.docx`: 120 mã, 15 cặp, nghề, môi trường, quy tắc.
- `database/seeders/data/holland_v3/`: dữ liệu chuẩn hóa (6 nhóm, 15 cặp, 6 chữ thứ ba, 46 ngành, nghề, liên kết, môi trường, trường thông tin, cài đặt). File `questions.json` trong đó là 54 câu giả cũ → **không dùng**, thay bằng thư viện 300 câu.
- `database/seeders/data/shared/regions.json`: khu vực ở Đức (dữ liệu thật, Ý thu thập).
- **Không dùng** các file bản cũ đã xóa (`Holland_de_xuat_nganh_v2*`, `DEMO_Holland_de_xuat_nganh.xlsx`, dữ liệu `database/seeders/data/holland/`).

## 6. Cách làm việc mỗi phiên

1. Đọc `docs/TIEN_DO.md` và `git log --oneline -15`.
2. Mỗi giai đoạn: **báo kế hoạch → chờ tôi duyệt → làm → `php artisan test` + `vendor/bin/pint --dirty` → commit → cập nhật `docs/TIEN_DO.md` → báo kết quả rồi dừng.**
3. Hết giai đoạn thì liệt kê việc trong bảng nghiệm thu đã làm được để tôi đưa Thầy thử.

## 7. Giai đoạn (bản làm lại)

- [x] 0. Nhánh `feature/holland-v3`, gỡ phần nghiệp vụ cũ (FIT, bộ câu Google Form, Phần A, 75 ngành), giữ hạ tầng
- [ ] 1. Bảng dữ liệu + nạp 300 câu, 60 chủ đề, danh mục, cài đặt, trường thông tin; trang Cài đặt bài test
- [ ] 2. Bộ máy: rút đề, chấm, phân tích, gợi ý ngành + test (8 ca của Thầy, ràng buộc rút đề)
- [ ] 3. Luồng khách: thông tin → làm bài → báo cáo khách → email
- [ ] 4. Admin thư viện câu hỏi + thống kê từng câu + nhập/xuất Excel
- [ ] 5. Admin danh mục (nhóm, cặp, 120 mã, ngành, nghề), trường thông tin khách, chú thích
- [ ] 6. Báo cáo nội bộ, vai trò tư vấn viên, lượt làm bài
- [ ] 7. API, CRM (outbox + thử lại), tài khoản, sao lưu, tài liệu, nghiệm thu

## 8. Lệnh hay dùng

```bash
composer install && cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # máy dev
php artisan serve                   # http://127.0.0.1:8000
php artisan test                    # chạy test
vendor/bin/pint --dirty             # định dạng code
php artisan queue:work              # chạy job gửi CRM (dev)
php artisan schedule:work           # chạy lịch (thử lại outbox, backup)
```

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.3. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
