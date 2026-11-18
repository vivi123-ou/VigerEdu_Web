<?php

namespace App\Filament\Resources\Banners\Tables;

use App\Models\Banner;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('STT')
                    ->sortable()
                    ->width('80px'),
                ImageColumn::make('image')
                    ->label('Hình ảnh')
                    ->state(fn (Banner $record) => $record->imageUrl)
                    ->width('120px'),
                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->searchable()
                    ->description(fn (Banner $record) => $record->eyebrow),
                TextColumn::make('button_primary_label')
                    ->label('Nút chính')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state, Banner $record) => $state ? "{$state} ({$record->button_primary_url})" : '-'),
                IconColumn::make('is_active')
                    ->label('Hiển thị')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                //
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
