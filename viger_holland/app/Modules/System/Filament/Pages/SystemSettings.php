<?php

namespace App\Modules\System\Filament\Pages;

use App\Filament\Pages\ContentSettingsPage;

/**
 * Làm gì:   Trang "Cài đặt hệ thống": nhật ký giữ bao lâu rồi tự dọn, sao lưu giữ bao lâu (hằng ngày, hằng tuần),
 *           nhân viên thấy khách của bao nhiêu ngày. Ô khai báo ở config/admin_content.php (khóa 'system').
 * Ai gọi:   Filament (/admin/cai-dat-he-thong). Quyền: system.manage (mặc định chỉ quản trị viên).
 * Dữ liệu:  settings; lịch sử ở admin_activities (settings.system).
 */
class SystemSettings extends ContentSettingsPage
{
    protected static ?string $slug = 'cai-dat-he-thong';

    protected static string $menuKey = 'system';

    protected static string $contentKey = 'system';
}
