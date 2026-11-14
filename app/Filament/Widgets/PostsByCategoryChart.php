<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

class PostsByCategoryChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Bài viết theo chuyên mục';

    protected ?string $maxHeight = '260px';

    private const COLORS = ['#c1121f', '#f59e0b', '#0ea5e9', '#10b981', '#8b5cf6', '#ec4899', '#64748b', '#14b8a6'];

    protected function getData(): array
    {
        $categories = Category::withCount('posts')
            ->has('posts')
            ->orderByDesc('posts_count')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'data' => $categories->pluck('posts_count')->all(),
                    'backgroundColor' => array_slice(self::COLORS, 0, $categories->count()),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $categories->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '65%',
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }
}
