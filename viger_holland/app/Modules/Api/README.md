# Module Api (API cho chatbot, CRM)

**Làm gì:** cho ứng dụng khác (chatbot của Ý, CRM) đọc kết quả Holland theo `docs/HOP_DONG_KET_NOI.md` mục 3. Tài liệu cho người lập trình: `docs/API_HOLLAND.md`.

- `GET /api/v1/results/{attempt_id}?view=khach|tuvan`: báo cáo khách / bản tư vấn (holland-result/3 + 36 câu). Không có dữ liệu cá nhân.
- `GET /api/v1/catalog/{types|pairs|third|majors}`: danh mục.
- Xác thực: header `X-Api-Token`, token lưu SHA-256, so `hash_equals`, phạm vi `results:read` (`Http/Middleware/AuthenticateApiClient.php`, alias `api.token`).

**Bảng:** `api_clients` (tên, băm token, 4 ký tự cuối, phạm vi, đang dùng, dùng lần cuối).

**Trang quản trị:** `Filament/Pages/ApiClients.php` – "Kết nối API (chatbot)" (`/admin/ket-noi-api`, quyền `api.manage`): cấp mã (hiện một lần), thu hồi, bật lại; ghi lịch sử.

**Test:** `tests/Feature/Api/HollandApiTest.php` (cùng test sao lưu và trang Hiển thị trên Google).
