<?php

namespace App\Modules\CrmSync\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Làm gì:   Cài đặt kết nối CRM: bật / tắt, địa chỉ, token, lịch gửi lại, mẫu link hồ sơ CRM.
 *           Địa chỉ và token trong .env (CRM_URL, CRM_TOKEN) được ưu tiên; không có thì lấy ở trang admin
 *           (token lưu mã hóa trong bảng settings, trang chỉ hiện 4 ký tự cuối).
 * Ai gọi:   CrmSync, SendToCrm, trang "Gửi khách sang CRM", báo cáo nội bộ.
 * Dữ liệu:  config services.crm, bảng settings (crm_*).
 */
class CrmConfig
{
    public function enabled(): bool
    {
        return (string) setting('crm_enabled', '0') === '1';
    }

    public function url(): string
    {
        return rtrim((string) (config('services.crm.url') ?: setting('crm_url', '')), '/');
    }

    public function token(): string
    {
        if (filled(config('services.crm.token'))) {
            return (string) config('services.crm.token');
        }
        $stored = (string) setting('crm_token', '');
        try {
            return $stored === '' ? '' : Crypt::decryptString($stored);
        } catch (Throwable) {
            return '';
        }
    }

    public function tokenFromEnv(): bool
    {
        return filled(config('services.crm.token'));
    }

    public function urlFromEnv(): bool
    {
        return filled(config('services.crm.url'));
    }

    public function saveToken(string $token): void
    {
        Setting::put('crm_token', $token === '' ? '' : Crypt::encryptString($token), 'crm');
    }

    /**
     * Gửi được khi đã bật và có đủ địa chỉ, token.
     */
    public function ready(): bool
    {
        return $this->enabled() && $this->url() !== '' && $this->token() !== '';
    }

    /**
     * Số phút chờ trước mỗi lần gửi lại (mặc định 1, 5, 30 phút, 2 giờ, 6 giờ).
     *
     * @return list<int>
     */
    public function retryMinutes(): array
    {
        $minutes = array_values(array_filter(array_map('intval', explode(',', (string) setting('crm_retry_minutes', '1,5,30,120,360'))), fn (int $m) => $m > 0));

        return $minutes ?: [1, 5, 30, 120, 360];
    }

    /**
     * Link mở hồ sơ khách trong CRM (mẫu có {ma}); để trống thì không hiện nút.
     */
    public function customerUrl(?string $customerCode): ?string
    {
        $template = (string) setting('crm_customer_url', '');

        return $template !== '' && filled($customerCode) ? str_replace('{ma}', rawurlencode((string) $customerCode), $template) : null;
    }
}
