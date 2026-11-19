<?php

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestPosts extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Bài viết mới nhất';

    public function table(Table $table): Table
    {
        return $table
            ->query(Post::query()->with('category')->latest('created_at')->limit(5))
            ->paginated(false)
            ->recordUrl(fn (Post $record) => PostResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->limit(60)
                    ->weight('medium'),
                TextColumn::make('category.name')
                    ->label('Chuyên mục')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (PostStatus $state) => $state === PostStatus::Published ? 'Đã xuất bản' : 'Bản nháp')
                    ->color(fn (PostStatus $state) => $state === PostStatus::Published ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i'),
            ]);
    }
}
