<?php

namespace App\Filament\Resources\Settings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Tên cài đặt')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->key),
                TextColumn::make('group')
                    ->label('Nhóm')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'general' => 'Chung',
                        'header' => 'Header',
                        'footer' => 'Footer',
                        'social' => 'Mạng xã hội',
                        'home' => 'Trang chủ',
                        'contact' => 'Trang Liên hệ',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'general' => 'primary',
                        'header' => 'info',
                        'footer' => 'warning',
                        'social' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('value')
                    ->label('Giá trị')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->label('Cập nhật lần cuối')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label('Nhóm cài đặt')
                    ->options([
                        'general' => 'Cài đặt chung',
                        'header' => 'Header',
                        'footer' => 'Footer',
                        'social' => 'Mạng xã hội',
                        'home' => 'Trang chủ',
                        'contact' => 'Trang Liên hệ',
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
