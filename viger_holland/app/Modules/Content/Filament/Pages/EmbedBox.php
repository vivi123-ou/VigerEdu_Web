<?php

namespace App\Modules\Content\Filament\Pages;

use App\Filament\Pages\ContentSettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Support\Components\Attributes\Unexposed;

/**
 * Làm gì:   Trang "Ô giới thiệu trên viger.edu.vn": tiêu đề, mô tả, ảnh, chữ nút của ô mời làm bài trên web chính,
 *           web nào được hiện ô; "Xem trước". Quản trị viên thấy thêm đoạn mã gửi người làm web chính (dùng một lần).
 * Ai gọi:   Filament (/admin/o-gioi-thieu). Quyền: content.manage.
 * Dữ liệu:  settings (nhóm embed); lịch sử ở admin_activities (settings.embed).
 */
class EmbedBox extends ContentSettingsPage
{
    protected static ?string $slug = 'o-gioi-thieu';

    protected static string $menuKey = 'embed';

    protected static string $contentKey = 'embed';

    #[Unexposed]
    protected function extraContent(): array
    {
        return [
            Section::make('Dành cho người làm web chính (chỉ dùng một lần lúc lắp đặt)')
                ->description('Lần đầu, chép một trong 3 đoạn dưới đây gửi người làm web chính. Sau đó sửa nội dung ở trên rồi bấm "Lưu" là web chính tự đổi (chậm nhất 5 phút).')
                ->collapsible()
                ->collapsed()
                ->visible(fn () => auth()->user()?->isAdmin())
                ->components([View::make('filament.pages.partials.embed-codes')]),
        ];
    }
}
