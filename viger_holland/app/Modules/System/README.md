# Module System (vận hành, bảo mật)

**Làm gì:** giữ cho web chạy lâu dài, không tràn dữ liệu, chống truy cập bất thường (ý Thầy 18/11).

**Trang quản trị** (quyền `system.manage`, mặc định chỉ quản trị viên):
- `Filament/Pages/SystemSettings.php` – **Cài đặt hệ thống** (`/admin/cai-dat-he-thong`): nhật ký giữ bao lâu, sao lưu giữ bao lâu, mức chống truy cập bất thường, IP văn phòng, số ngày "Khách cần liên lạc". Ô khai báo ở `config/admin_content.php` (khóa `system`).
- `Filament/Pages/BlockedIps.php` – **IP bị chặn** (`/admin/ip-bi-chan`): xem, bỏ chặn, chặn tay.

**Lệnh chạy nền** (`routes/console.php`, cần cron `* * * * * php artisan schedule:run`):

| Lệnh | Khi nào | Làm gì |
|---|---|---|
| `holland:backup` | 02:00 hằng đêm | Sao lưu; giữ mọi bản 14 ngày + mỗi tuần 1 bản trong 8 tuần |
| `holland:prune` (`--dry-run` để chỉ đếm) | 03:00 hằng đêm | Xóa lịch sử thao tác, hành vi khách > 12 tháng; gói CRM đã gửi > 90 ngày; mã email hết hạn; IP hết hạn chặn; file log > 30 ngày. **Không đụng dữ liệu khách.** |
| `holland:security-audit` | Thứ Hai 04:00 | `composer audit`: có lỗ hổng → ghi Lịch sử thao tác + log |

**Chống truy cập bất thường** (`app/Http/Middleware/GuardAgainstAbuse.php` + `Services/IpGuard.php`, cấu hình `config/security.php`):
- IP đang bị chặn → 403 ngay; đường hacker hay dò (`/wp-admin`, `/.env`, `phpmyadmin`, file `.sql`…) → 404 ngay, tính 5 lần vi phạm;
- quá 300 yêu cầu / phút / IP (đổi được) → 429, tính 1 lần vi phạm; đủ 10 lần trong 10 phút → tự chặn 60 phút;
- IP văn phòng (ô "IP không bao giờ bị chặn") không bao giờ bị chặn.
- `app/Http/Middleware/SecurityHeaders.php`: X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, HSTS khi HTTPS, bỏ X-Powered-By.
- Chạy sau Cloudflare: đặt `TRUSTED_PROXIES` trong `.env` để lấy đúng IP thật.

**Không làm được trong code:** tấn công từ hàng loạt IP (DDoS) → chặn ở Cloudflare / tường lửa host (xem `docs/SO_TAY_SU_DUNG.md` mục 6).

**Test:** `tests/Feature/System/SystemMaintenanceTest.php`, `SecurityGuardTest.php`.
