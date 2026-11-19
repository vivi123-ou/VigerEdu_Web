# VIGER EDU – hệ thống web tư vấn du học Đức

Một repo chung gồm 4 ứng dụng **độc lập**, nối với nhau **chỉ qua API**. Mỗi ứng dụng có `README`, `.env.example` và cách chạy riêng, gỡ / thay một ứng dụng không ảnh hưởng ứng dụng khác.

| Thư mục | Ứng dụng | Địa chỉ | Công nghệ | Người phụ trách |
|---|---|---|---|---|
| `viger_web/` | Web chính | viger.edu.vn | Laravel | Tuấn |
| `viger_holland/` | Trắc nghiệm Holland (VIGER EDU Career Map) | holland.viger.edu.vn | Laravel 13, Filament 5 | Vân |
| `viger_crm/` | CRM nội bộ (đang thiết kế, chưa có code) | crm.viger.edu.vn | Laravel | Vân |
| `viger_chatbot/` | AI chatbot tư vấn (đang trống) | | Python | Team IT |

Tài liệu dùng chung:
- Thiết kế tổng thể: `viger_crm/docs/THIET_KE_TONG_THE_V1.md`
- Hợp đồng kết nối giữa các ứng dụng (API, định dạng dữ liệu): `viger_crm/docs/HOP_DONG_KET_NOI.md`
- Từ điển dữ liệu chung (tên trường, mã lựa chọn): `viger_crm/docs/TU_DIEN_DU_LIEU_CHUNG.xlsx`

## Quy tắc chung

- Mỗi người làm trong thư mục ứng dụng của mình; sửa thư mục của người khác thì báo trước.
- **Không đưa `.env`, mật khẩu, token lên git.** Biến cần có ghi vào `.env.example` của từng ứng dụng.
- Không ghi số điện thoại, email, họ tên khách vào log.
- Commit message tiếng Việt, nói rõ sửa ứng dụng nào.

## Chạy một ứng dụng Laravel trên máy

```bash
cd viger_holland          # hoặc viger_web
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve
```
