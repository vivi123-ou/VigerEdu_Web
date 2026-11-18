# Module CrmSync (gửi khách sang CRM)

**Làm gì:** khách nhập đúng mã email → gói `lead/2` (theo `docs/HOP_DONG_KET_NOI.md` mục 2.1) vào hàng đợi `crm_outbox` → job `SendToCrm` gửi nền. CRM lỗi, chưa có hay đang tắt thì khách **vẫn xem báo cáo bình thường**; gói nằm chờ và tự gửi lại. Hành vi sau khi CRM đã trả mã khách gửi bằng `events/1`.

**Bảng:** `crm_outbox` (event_id giữ nguyên khi gửi lại; payload **mã hóa**; trạng thái chờ gửi / đã gửi / lỗi / đang tắt kết nối; `last_error` không có dữ liệu cá nhân), `respondents.crm_customer_code`.

**Code:**
- `Services/LeadPayload.php`: dựng `lead/2`. Ô form đi theo `crm_key` → vị trí trong gói ở `config/crm.php` (ô không có trong bảng ánh xạ → `extra_fields`); chỉ gửi ô có giá trị và `to_crm`; SĐT dạng `+84…`; SĐT phụ huynh → `guardian.phone`; ngày sinh chỉ gửi **năm**; `consent.agreed = true` **chỉ khi** khách thật sự thấy câu đồng ý (có dòng `consents`), không thì `agreed = false`; `holland_result/3`, 36 câu, hành vi, UTM, link báo cáo nội bộ.
- `Services/CrmSync.php`: `queueLead`, `queueEvents`, `retry`, `resumeDisabled`.
- `Jobs/SendToCrm.php`: 2xx → đã gửi (lưu mã khách); 422 → lỗi (chỉ ghi tên trường); 401 / 403 / 429 / 5xx / mất mạng → gửi lại theo `crm_retry_minutes` (1, 5, 30, 120, 360 phút), hết lượt → lỗi.
- `Services/CrmConfig.php`: bật / tắt, địa chỉ, token (`.env` `CRM_URL`, `CRM_TOKEN` được ưu tiên; nhập ở admin thì lưu mã hóa).
- `Console/RetryCrm.php` (`php artisan crm:retry`, lịch mỗi 5 phút trong `routes/console.php`), `Console/ShowCrmPayload.php` (`php artisan crm:payload {mã báo cáo}` in gói **đã che**).
- `Filament/Pages/CrmConnection.php`: trang "Gửi khách sang CRM" (`/admin/gui-khach-sang-crm`, quyền `crm.manage`).

**Chạy trên máy:** `php artisan queue:work` (gửi) và `php artisan schedule:work` (gửi lại). Chưa có CRM thì để tắt: gói nằm ở "Đang tắt kết nối".

**Bảo mật:** log chỉ có `event_id`, mã HTTP, số lần thử. Bảng hàng đợi chỉ hiện SĐT đã che (`09xx xxx 678`).

**Test:** `tests/Feature/CrmSync/CrmSyncTest.php` (11 ca, CRM giả bằng `Http::fake()`).
