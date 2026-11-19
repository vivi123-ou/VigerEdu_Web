# VigerEdu Web

Website chính của VigerEdu – tư vấn du học Đức, xây dựng bằng Laravel.

## Yêu cầu

- PHP 8.2+
- Composer
- MySQL (hoặc SQLite)

## Cài đặt

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Cấu hình database trong `.env`, đặt `ADMIN_USERNAME` và `ADMIN_PASSWORD`, sau đó chạy:

```bash
php artisan migrate --seed
php artisan serve
```

Trang quản trị: `/login` → `/admin/posts`.

## License

MIT
