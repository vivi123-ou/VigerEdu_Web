<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PostsPerMonthChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Bài viết theo tháng';

    protected ?string $description = '12 tháng gần nhất';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths(11);

        $counts = Post::where('created_at', '>=', $start)
            ->pluck('created_at')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m'))
            ->countBy();

        $months = collect(range(0, 11))->map(fn (int $i) => $start->copy()->addMonths($i));

        return [
            'datasets' => [
                [
                    'label' => 'Bài viết',
                    'data' => $months->map(fn (Carbon $m) => $counts->get($m->format('Y-m'), 0))->all(),
                    'fill' => true,
                    'tension' => 0.35,
                    'borderColor' => '#c1121f',
                    'backgroundColor' => 'rgba(193, 18, 31, 0.12)',
                    'pointBackgroundColor' => '#c1121f',
                ],
            ],
            'labels' => $months->map(fn (Carbon $m) => 'T'.$m->format('n/y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
