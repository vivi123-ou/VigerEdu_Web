<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Support\TestConfig;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Làm gì:   4 ô số liệu nhanh ở Trang chủ quản trị: câu hỏi đang dùng, câu tạm ngưng, người làm bài, cách bốc đề đang dùng.
 *           Chỉ hiện cho người được xem số liệu hoặc được sửa câu hỏi (mặc định chỉ quản trị viên).
 * Ai gọi:   Dashboard (Filament tự tìm widget trong app/Filament/Widgets).
 * Dữ liệu:  bảng questions, test_sessions, test_config_versions. Không có dữ liệu cá nhân của khách.
 */
class HollandOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /** Truy vấn nhẹ nên hiện ngay cùng trang, không tải sau. */
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && ($user->hasPermission('stats.view') || $user->hasPermission('questions.manage'));
    }

    protected function getStats(): array
    {
        $statusCounts = Question::query()->pluck('review_status')->countBy();
        $approvedByGroup = Question::query()->approved()->pluck('group')->countBy();
        $needed = (int) TestConfig::get('items_per_group');
        $shortGroups = collect(['R', 'I', 'A', 'S', 'E', 'C'])->filter(fn (string $group) => ($approvedByGroup[$group] ?? 0) < $needed);
        $version = TestConfig::currentVersion();

        return [
            Stat::make('Câu hỏi đang dùng', $statusCounts[Question::STATUS_APPROVED] ?? 0)
                ->description($shortGroups->isEmpty()
                    ? 'Đủ để ra đề '.($needed * 6).' câu'
                    : 'Nhóm thiếu câu để ra đề: '.$shortGroups->implode(', '))
                ->color($shortGroups->isEmpty() ? 'success' : 'danger'),
            Stat::make('Câu tạm ngưng / nháp', ($statusCounts[Question::STATUS_SUSPENDED] ?? 0).' / '.($statusCounts[Question::STATUS_DRAFT] ?? 0))
                ->description('Không được đưa vào đề'),
            Stat::make('Người làm bài hôm nay', TestSession::query()->whereDate('created_at', today())->count())
                ->description('Tổng cộng: '.TestSession::query()->count()),
            Stat::make('Cách bốc đề đang dùng', $version ? 'Bản '.$version->id : 'Mặc định')
                ->description($version ? 'Lưu lúc '.$version->created_at->format('H:i d/m/Y') : 'Chưa lưu lần nào'),
        ];
    }
}
