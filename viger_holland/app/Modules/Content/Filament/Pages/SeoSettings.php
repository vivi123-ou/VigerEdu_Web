<?php

namespace App\Modules\Content\Filament\Pages;

use App\Filament\Pages\ContentSettingsPage;

/**
 * Làm gì:   Trang "Hiển thị trên Google": tiêu đề, mô tả, ảnh khi chia sẻ link trang bắt đầu.
 *           Ô của trang khai báo ở config/admin_content.php (khóa 'seo').
 * Ai gọi:   Filament (/admin/hien-thi-tren-google). Quyền: appearance.manage (Quản trị viên).
 * Dữ liệu:  settings; lịch sử ở admin_activities (settings.seo).
 */
class SeoSettings extends ContentSettingsPage
{
    protected static ?string $slug = 'hien-thi-tren-google';

    protected static string $menuKey = 'seo';

    protected static string $contentKey = 'seo';
}
