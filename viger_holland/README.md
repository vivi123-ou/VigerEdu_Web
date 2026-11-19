# Web Test Holland

Web trắc nghiệm Holland (RIASEC) chạy độc lập: khách làm bài, xem kết quả; thông tin và bài làm được gửi sang CRM qua API.
Kết nối với các web khác theo `docs/HOP_DONG_KET_NOI.md`.

- Laravel 13, PHP ≥ 8.3, MySQL (chạy thật) / SQLite trong bộ nhớ (test tự động)
- Blade + CSS/JS thuần (không Tailwind, không cần Node/npm)

> Hướng dẫn cài lên VPS Linux (nginx, HTTPS, cron, queue worker) sẽ bổ sung ở giai đoạn 7.

## Chạy trên máy (Windows + Laragon)

1. Mở Laragon (`D:\laragon\laragon.exe`) → bấm **Start All** (bật MySQL).
2. Tạo database (chỉ làm 1 lần):
   ```bash
   mysql -uroot -e "CREATE DATABASE IF NOT EXISTS holland CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```
3. Cài đặt:
   ```bash
   composer install
   cp .env.example .env        # rồi điền HOLLAND_ADMIN_USERNAME / HOLLAND_ADMIN_PASSWORD
   php artisan key:generate
   php artisan migrate --seed  # tạo bảng + 72 câu hỏi mẫu + nội dung mẫu + tài khoản quản trị
   php artisan serve           # mở http://127.0.0.1:8000
   ```
4. Trang quản trị: `http://127.0.0.1:8000/admin` (đăng nhập bằng tên đăng nhập hoặc email).

Seeder chạy lại bao nhiêu lần cũng được: chỉ thêm cái chưa có, **không ghi đè** câu hỏi / nội dung / mật khẩu đã sửa.

## Lệnh hay dùng

```bash
php artisan test              # chạy toàn bộ test
vendor/bin/pint --dirty       # định dạng code
php artisan queue:work        # chạy job gửi CRM (từ giai đoạn 4)
php artisan schedule:work     # chạy lịch thử lại / sao lưu (từ giai đoạn 4, 6)
```

## Cấu hình `.env` riêng của web Holland

| Khóa | Ý nghĩa |
|---|---|
| `HOLLAND_ENGINE` | Thuật toán chấm điểm (mặc định `riasec_fit_v2`) |
| `HOLLAND_CRM_ENABLED`, `HOLLAND_CRM_URL`, `HOLLAND_CRM_TOKEN` | Kết nối CRM; tắt thì chạy chế độ dự phòng |
| `HOLLAND_PURGE_CONTACT` | Xóa SĐT/email sau khi CRM xác nhận (mặc định `false`) |
| `HOLLAND_CHAT_WIDGET_URL` | Script chatbot (để trống = không chèn) |
| `HOLLAND_ADMIN_*` | Tài khoản quản trị đầu tiên do seeder tạo |

Mọi chữ, hình, thông tin đơn vị khách nhìn thấy nằm trong bảng `settings` và sửa trên trang quản trị, **không** ghi cứng trong code.

## An toàn dữ liệu

Khi `APP_ENV=production`, các lệnh `migrate:fresh`, `migrate:reset`, `db:wipe` bị chặn.
