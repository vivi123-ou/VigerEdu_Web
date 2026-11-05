<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\PostStatus;
use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('Ảnh')
                    ->state(function (Post $record) {
                        if (empty($record->featured_image)) {
                            return null;
                        }
                        if (Str::startsWith($record->featured_image, ['http://', 'https://', '//'])) {
                            return $record->featured_image;
                        }
                        if (Storage::disk('public')->exists($record->featured_image)) {
                            return Storage::disk('public')->url($record->featured_image);
                        }

                        return asset($record->featured_image);
                    })
                    ->width('80px'),
                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Post $record) => Str::limit($record->excerpt, 60)),
                TextColumn::make('category.name')
                    ->label('Chuyên mục')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PostStatus ? ($state === PostStatus::Published ? 'Đã xuất bản' : 'Bản nháp') : ($state === 'PUBLISHED' ? 'Đã xuất bản' : 'Bản nháp'))
                    ->color(fn ($state) => ($state instanceof PostStatus ? $state->value : $state) === 'PUBLISHED' ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Chuyên mục')
                    ->relationship('category', 'name'),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        PostStatus::Published->value => 'Đã xuất bản',
                        PostStatus::Draft->value => 'Bản nháp',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
