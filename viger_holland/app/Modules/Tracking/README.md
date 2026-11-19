# Module Tracking (hành vi của khách)

**Làm gì:** ghi hành vi trong lượt làm bài theo bảng loại sự kiện của hợp đồng (mục 2.2), gửi kèm sang CRM.

- Máy chủ tự ghi: `quiz_started`, `quiz_page_completed`, `quiz_finished`, `form_submitted`, `report_unlocked`, `report_viewed`.
- Trình duyệt gửi qua `POST /t` (60 lần / phút, chỉ nhận loại trong `config/crm.php` → `browser_events`): `cta_call_clicked`, `cta_zalo_clicked` (nút Gọi / Zalo trên báo cáo, `navigator.sendBeacon`).
- Trước khi CRM có mã khách: hành vi đi kèm gói `lead/2`. Sau đó: gửi ngay bằng `events/1`.

**Bảng:** `tracking_events` (gắn lượt làm bài, `meta` không có dữ liệu cá nhân, `sent_at` = đã đưa vào gói gửi).

**Code:** `Services/Tracker.php`, `Http/Controllers/TrackController.php`.
