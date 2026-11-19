<?php

namespace App\Modules\Content\Filament\Pages;

use App\Filament\Pages\ContentSettingsPage;

/**
 * Làm gì:   Trang "Logo, màu, hotline, chân trang": tên đơn vị, logo, màu, font, hotline, địa chỉ hiện trên mọi trang khách.
 *           Ô của trang khai báo ở config/admin_content.php (khóa 'appearance').
 * Ai gọi:   Filament (/admin/logo-mau-hotline). Quyền: appearance.manage (Quản trị viên).
 * Dữ liệu:  settings; lịch sử ở admin_activities (settings.appearance).
 */
class Appearance extends ContentSettingsPage
{
    protected static ?string $slug = 'logo-mau-hotline';

    protected static string $menuKey = 'appearance';

    protected static string $contentKey = 'appearance';
}
