<?php

namespace App\Filament\Widgets;

use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Support\TestConfig;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Làm gì:   4 ô số liệu trên Bảng điều khiển: câu đã duyệt, câu tạm ngưng / nháp, lượt làm bài, phiên bản cài đặt.
 * Ai gọi:   Dashboard (Filament tự tìm widget trong app/Filament/Widgets).
 * Dữ liệu:  bảng questions, test_sessions, test_config_versions. Không có dữ liệu cá nhân của khách.
 */
class HollandOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /** Truy vấn nhẹ nên hiện ngay cùng trang, không tải sau. */
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $statusCounts = Question::query()->pluck('review_status')->countBy();
        $approvedByGroup = Question::query()->approved()->pluck('group')->countBy();
        $needed = (int) TestConfig::get('items_per_group');
        $shortGroups = collect(['R', 'I', 'A', 'S', 'E', 'C'])->filter(fn (string $group) => ($approvedByGroup[$group] ?? 0) < $needed);
        $version = TestConfig::currentVersion();

        return [
            Stat::make('Câu hỏi đã duyệt', $statusCounts[Question::STATUS_APPROVED] ?? 0)
                ->description($shortGroups->isEmpty()
                    ? 'Đủ câu để rút đề '.($needed * 6).' câu'
                    : 'Nhóm thiếu câu để rút đề: '.$shortGroups->implode(', '))
                ->color($shortGroups->isEmpty() ? 'success' : 'danger'),
            Stat::make('Câu tạm ngưng / nháp', ($statusCounts[Question::STATUS_SUSPENDED] ?? 0).' / '.($statusCounts[Question::STATUS_DRAFT] ?? 0))
                ->description('Không được rút vào đề'),
            Stat::make('Lượt làm bài', TestSession::query()->count())
                ->description('Hôm nay: '.TestSession::query()->whereDate('created_at', today())->count()),
            Stat::make('Cài đặt bài test', $version ? 'Phiên bản '.$version->id : 'Mặc định')
                ->description($version ? 'Lưu lúc '.$version->created_at->format('H:i d/m/Y') : 'Chưa lưu lần nào'),
        ];
    }
}
