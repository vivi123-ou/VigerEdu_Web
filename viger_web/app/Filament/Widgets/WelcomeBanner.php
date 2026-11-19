<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Posts\PostResource;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class WelcomeBanner extends Widget
{
    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.welcome-banner';

    protected function getViewData(): array
    {
        $now = Carbon::now();
        $hour = (int) $now->format('G');

        $weekdays = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];

        return [
            'greeting' => match (true) {
                $hour < 11 => 'Chào buổi sáng',
                $hour < 14 => 'Chào buổi trưa',
                $hour < 18 => 'Chào buổi chiều',
                default => 'Chào buổi tối',
            },
            'userName' => filament()->getUserName(filament()->auth()->user()),
            'today' => $weekdays[$now->dayOfWeek].', '.$now->format('d/m/Y'),
            'createPostUrl' => PostResource::getUrl('create'),
            'bannersUrl' => BannerResource::getUrl('index'),
            'siteUrl' => url('/'),
        ];
    }
}
