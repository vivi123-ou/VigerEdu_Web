<?php

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Models\Banner;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Post;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -5;

    protected function getStats(): array
    {
        $published = Post::published()->count();
        $drafts = Post::where('status', PostStatus::Draft)->count();
        $newContacts = ContactMessage::where('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make('Bài viết đã xuất bản', $published)
                ->description($drafts.' bản nháp đang chờ')
                ->descriptionIcon(Heroicon::OutlinedPencilSquare)
                ->icon(Heroicon::OutlinedNewspaper)
                ->chart($this->dailyCounts(Post::query()))
                ->color('primary'),
            Stat::make('Chuyên mục', Category::count())
                ->description(Category::where('is_in_menu', true)->count().' mục hiển thị trên menu')
                ->descriptionIcon(Heroicon::OutlinedBars3)
                ->icon(Heroicon::OutlinedFolder)
                ->color('info'),
            Stat::make('Banner đang hiển thị', Banner::active()->count())
                ->description('Trên tổng số '.Banner::count().' banner')
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->icon(Heroicon::OutlinedPhoto)
                ->color('warning'),
            Stat::make('Liên hệ mới (7 ngày)', $newContacts)
                ->description('Tổng cộng '.ContactMessage::count().' liên hệ')
                ->descriptionIcon(Heroicon::OutlinedInboxArrowDown)
                ->icon(Heroicon::OutlinedEnvelope)
                ->chart($this->dailyCounts(ContactMessage::query()))
                ->color('success'),
        ];
    }

    /**
     * Số bản ghi tạo mỗi ngày trong 7 ngày gần nhất (cho sparkline).
     *
     * @return array<int, int>
     */
    private function dailyCounts(Builder $query): array
    {
        $start = Carbon::today()->subDays(6);

        $dates = $query->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->countBy();

        return collect(range(0, 6))
            ->map(fn (int $i) => $dates->get($start->copy()->addDays($i)->toDateString(), 0))
            ->all();
    }
}
