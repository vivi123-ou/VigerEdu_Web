<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('banner_image')
                    ->label('Ảnh bìa')
                    ->state(fn (Page $record) => $record->bannerImageUrl)
                    ->width('100px'),
                TextColumn::make('title')
                    ->label('Tiêu đề trang')
                    ->searchable()
                    ->description(fn (Page $record) => $record->subtitle),
                TextColumn::make('slug')
                    ->label('Đường dẫn (URL)')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => "/{$state}")
                    ->searchable(),
                IconColumn::make('is_published')
                    ->label('Công khai')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime('d/m/Y H:i')
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
