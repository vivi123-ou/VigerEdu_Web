<?php

namespace App\Modules\Content\Filament\Pages;

use App\Filament\Pages\ContentSettingsPage;

/**
 * Làm gì:   Trang "Chữ trên báo cáo & email": mọi câu chữ khách đọc từ lúc làm bài đến báo cáo đầy đủ và email gửi mã.
 *           Ô của trang khai báo ở config/admin_content.php (khóa 'report').
 * Ai gọi:   Filament (/admin/chu-tren-bao-cao). Quyền: content.manage (Nhân viên, Quản trị viên).
 * Dữ liệu:  settings; lịch sử ở admin_activities (settings.report).
 */
class ReportTexts extends ContentSettingsPage
{
    protected static ?string $slug = 'chu-tren-bao-cao';

    protected static string $menuKey = 'report-pages';

    protected static string $contentKey = 'report';
}
