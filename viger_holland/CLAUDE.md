# CLAUDE.md – VigerEdu_Holland (web Test Holland, `holland.viger.edu.vn`)

> File này Claude Code tự đọc mỗi phiên. **Đặc tả đầy đủ:** `docs/PROMPT_2_HOLLAND.md`, đọc khi bắt đầu một giai đoạn mới hoặc khi chưa rõ yêu cầu.
> **Tiến độ:** `docs/TIEN_DO.md`. Đọc đầu mỗi phiên và cập nhật cuối mỗi phiên.

## 1. Tôi là ai, dự án là gì

- Tôi là **Vân (Vivi)**, Team Công nghệ, thực tập tại **Viger-Edu** (tư vấn du học Đức). Giao tiếp bằng **tiếng Việt**.
- Hệ thống gồm **3 platform độc lập + 1 chatbot**, nối với nhau **chỉ qua API** (`docs/HOP_DONG_KET_NOI.md`):
  1. `viger.edu.vn`: web chính, Tuấn làm (Laravel, có admin quản lý thư mục, bài viết).
  2. **`holland.viger.edu.vn`: repo này.** Tôi làm. Khách (học sinh / phụ huynh) làm bài test Holland, xem kết quả; thông tin và bài làm được gửi sang CRM.
  3. `crm.viger.edu.vn`: CRM, tôi làm sau, ở repo riêng `VigerEdu_CRM`. Hiện **chưa có**, nên mọi kết nối CRM phải chạy được ở **chế độ dự phòng** và test bằng `Http::fake()`.
  4. AI chatbot: **Python**, bạn Ý làm, độc lập. Gắn vào trang bằng ô "Mã nhúng chatbot" trong admin; gọi API của repo này để lấy câu hỏi và chấm điểm.
- Yêu cầu của Thầy (người quản lý, **không làm IT**):
  - mỗi platform **gỡ ra / thay thế được, không sửa code**;
  - **mọi thứ khách thấy và mọi danh mục đều sửa / thêm được trên admin**;
  - dữ liệu khách tập trung ở CRM, được bảo mật.

## 2. Repo này làm gì

- **Trang khách** (thiết kế cho điện thoại trước):
  - giới thiệu → **Phần A** (form thông tin, động, admin tự thêm câu) → **Phần B** (bộ câu Holland, mặc định 54 câu: 6 nhóm × 9);
  - **trang kết quả**: biểu đồ 6 nhóm, mã 3 chữ, lĩnh vực và ngành gợi ý; **không hiện điểm FIT dạng số, không hiện GREEN/YELLOW/RED, không hứa đậu**; nút đặt lịch, Gọi / Zalo;
  - trang kết quả `noindex`.
- **Admin** (`/admin`), theo bố cục `docs/anh_mau_quan_tri/H19_admin_holland.png`:
  - bộ câu hỏi (sửa trên web hoặc qua Excel), form Phần A, cấu hình chấm điểm (có nút "Chạy thử");
  - nội dung trang (banner, chữ, hình, email, khối cho web chính), lượt làm bài, hàng đợi gửi CRM;
  - kết nối, cài đặt, SEO, tài khoản, sao lưu.
- **API** `/api/v1`: `GET questions`, `POST score`, `GET public/intro`, `GET embed`, `embed.js` (theo hợp đồng).
- **Gửi sang CRM**: `POST {CRM}/api/v1/leads` với gói `lead/1` + `holland_result` + `quiz` (bài làm từng câu) + `extra_fields`, qua **bảng outbox + job thử lại**.

## 3. Công nghệ và quy tắc

- **Laravel 13, PHP ≥ 8.3**, MySQL (production) / SQLite (dev, test), Blade + CSS/JS thuần (không Tailwind, không SPA).
- Package ngoài Laravel: **chỉ `openspout/openspout`** đã được duyệt. Muốn thêm package khác thì **hỏi tôi trước**.
- Git: làm trên nhánh `dev`, commit theo giai đoạn, message tiếng Việt. **Không push, không merge.**
- Giao diện và thông báo **tiếng Việt**. Class, bảng, cột **tiếng Anh**.
- **Tên trường, khóa API, mã, giá trị lựa chọn: đúng `docs/TU_DIEN_DU_LIEU_CHUNG.xlsx` và `docs/lua_chon_chuan.json`.** Không tự đặt tên khác.
- **Nguyên tắc "mở"**: không ghi cứng chữ, hình, lựa chọn, tên miền, chữ "Viger" trong code/view; lấy từ bảng `settings` / nội dung admin.
- **Dễ dùng cho người không làm IT**: làm theo `docs/TIEU_CHI_DE_DUNG_VA_NGHIEM_THU.md` (10 quy tắc: nút "?", xem trước, lịch sử, thùng rác, ≤ 3 lần bấm…).
- Bảo mật:
  - FormRequest cho mọi input, `{{ }}` trong Blade, CSRF, throttle form công khai (5/phút) và API (60/phút);
  - `result_token` là UUID; token API lưu dạng hash, so sánh `hash_equals`;
  - **không ghi SĐT/email vào log**;
  - migration **chỉ thêm**; chặn `migrate:fresh` / `reset` / `db:wipe` khi production.
- Tài liệu chỉ viết: `README.md`, `docs/API_HOLLAND.md`, `docs/THAY_THUAT_TOAN.md`, `docs/SO_TAY_SU_DUNG.md`, `docs/TIEN_DO.md`, `docs/NGHIEM_THU.md`.
- Chưa rõ hoặc thấy mâu thuẫn: **hỏi tôi**, đừng đoán.

## 4. Thuật toán (KHÔNG đổi công thức – port từ `database/seeders/data/holland/holland_engine_reference.php`)

Đặt sau interface `App\Contracts\ScoringEngine`, bản mặc định là `RiasecFitEngineV2`, chọn engine bằng cấu hình.
1. `p[g]` = tổng điểm câu nhóm g / tổng điểm tối đa nhóm g. Chạy được với mọi số câu và mọi thang trả lời.
2. Mã = 3 nhóm có p cao nhất; **bằng nhau thì xếp theo R, I, A, S, E, C**.
3. Cảnh báo (không đổi xếp hạng):
   - `di3 = p1 − (p2 + p4)/2`, dưới 0,15 là "hồ sơ phẳng";
   - `consistency = s(X1, X2)`;
   - `has_tie` khi p1 = p2, hoặc p2 = p3, hoặc p3 = p4.
4. `s(x, y) = 3 − min(|i − j|, 6 − |i − j|)` theo thứ tự "RIASEC" (trùng 3, kề 2, cách một 1, đối diện 0).
5. Khớp mã: `C = 3·s(X1,Y1) + 2·s(X2,Y2) + s(X3,Y3)`, `code_match = C/18` (X = mã học sinh, Y = mã ngành).
6. Khớp điểm: `r = (3·p[Y1] + 2·p[Y2] + p[Y3] − Σp) / (√8 · √Σ(p − p̄)²)` (mẫu số = 0 thì r = 0), `score_match = (r + 1)/2`.
7. `FIT = 0,6·code_match + 0,4·score_match` (trọng số sửa được trên admin). Ngành có 1–3 mã thì lấy mã cho FIT cao nhất.
8. Nhãn: ≥ 0,80 Rất phù hợp · ≥ 0,65 Phù hợp · ≥ 0,50 Có thể cân nhắc · còn lại Ít phù hợp.
9. Xếp hạng ngành đang bật, tách đại học / học nghề, sắp theo FIT → code_match → mã; lấy top 5 + top 3.
10. Điểm lĩnh vực / nhóm TestAS = FIT cao nhất trong nhóm. `reason` có dạng `"AIS ↔ IAS · bạn đạt I 67%, A 89%, S 56%"`.
11. Kết quả trả về theo cấu trúc **`holland-result/1`** (`docs/HOP_DONG_KET_NOI.md` mục 4).

**Kiểm tra:** unit test theo `database/seeders/data/holland/test_cases.json`, sai số 0,001. Ví dụ HS01: mã AIS, ngành đầu tiên N043 (Ngôn ngữ và Văn học Đức) FIT ≈ 0,8011.

## 5. Dữ liệu có sẵn

- `database/seeders/data/holland/`:
  - `questions.json`: 69 câu, gồm A01–A15 và B01–B54 (**câu B là câu mẫu**, Thầy sẽ thay);
  - `holland_config.json`, `majors.json` (75 ngành / nghề, bản dự phòng khi chưa có CRM), `test_cases.json`, `holland_engine_reference.php`.
- `docs/`:
  - `HOP_DONG_KET_NOI.md`, `TU_DIEN_DU_LIEU_CHUNG.xlsx`, `lua_chon_chuan.json`, `TIEU_CHI_DE_DUNG_VA_NGHIEM_THU.md`;
  - `3_Mau_GoogleSheet_Viger_Holland_v2.xlsx` (tab CAU_HOI = cột của file Excel câu hỏi);
  - `Holland_de_xuat_nganh_v2.1.docx` (giải thích thuật toán từng công thức) + `Holland_de_xuat_nganh_v2.xlsx`, `DEMO_Holland_de_xuat_nganh.xlsx` (5 học sinh mẫu);
  - `Phan_tich_thiet_ke_he_thong_Holland.docx`, `De_xuat_cau_truc_quan_tri_web_Viger.docx`, `anh_mau_quan_tri/`.

## 6. Cách làm việc mỗi phiên

1. Đọc `docs/TIEN_DO.md` và `git log --oneline -15`.
2. Nói ngắn gọn sẽ làm gì trong phiên này (giai đoạn nào, những file nào). Việc lớn thì **chờ tôi đồng ý**.
3. Làm, rồi chạy `php artisan test` và `vendor/bin/pint --dirty`.
4. Commit, cập nhật `docs/TIEN_DO.md` (đã xong gì, còn gì, câu hỏi cho tôi), báo cáo ngắn.
5. Hết giai đoạn thì liệt kê các việc trong **bảng nghiệm thu** đã làm được, để tôi đưa Thầy và marketing thử.

## 7. Giai đoạn (chi tiết ở `docs/PROMPT_2_HOLLAND.md` mục 8)

- [ ] 1. Khởi tạo Laravel 13, Laravel Boost, layout mobile-first, đăng nhập admin, seeder 69 câu
- [ ] 2. Contracts + `RiasecFitEngineV2` + `JsonMajorCatalog` + unit test theo `test_cases.json` + test engine giả
- [ ] 3. Form Phần A động, nhiều bộ câu, cấu hình chấm điểm trên admin, trang làm bài, kết quả, email, đặt lịch, quản trị nội dung trang, khối nhúng
- [ ] 4. Kết nối CRM: catalog, lead sink, outbox + retry + scheduler (test bằng `Http::fake()`)
- [ ] 5. API v1 + `docs/API_HOLLAND.md` (curl + Python `requests` cho bạn Ý)
- [ ] 6. An toàn dữ liệu: backup / restore, chặn lệnh xóa database, gỡ / cài lại
- [ ] 7. Admin câu hỏi / thống kê / outbox, xuất nhập Excel, trang Cài đặt và kết nối, SEO, `/install`, README deploy VPS Linux, sổ tay sử dụng

## 8. Lệnh hay dùng

```bash
composer install && cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # dev (SQLite)
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
