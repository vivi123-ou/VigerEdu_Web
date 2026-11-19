<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn ($query) => $query->orderBy('menu_group')->orderBy('sort_order'))
            ->defaultGroup(
                Group::make('menu_group')
                    ->label('Nhóm menu')
                    ->getTitleFromRecordUsing(fn (Category $record) => Category::GROUP_LABELS[(string) $record->menu_group] ?? 'Không hiển thị trên menu')
                    ->collapsible()
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Tên chuyên mục')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('menu_group')
                    ->label('Nhóm Menu Header')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Category::GROUP_LABELS[(string) $state] ?? 'Không hiển thị')
                    ->color(fn ($state) => match ($state) {
                        'giao-duc-tai-duc' => 'info',
                        'tu-van' => 'warning',
                        'blog' => 'success',
                        'main' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('parent.name')
                    ->label('Thuộc dropdown')
                    ->placeholder('—'),
                ToggleColumn::make('is_in_menu')
                    ->label('Trên Menu')
                    ->disabled(fn ($record) => blank($record->menu_group))
                    ->sortable(),
                TextInputColumn::make('sort_order')
                    ->label('Thứ tự')
                    ->type('number')
                    ->rules(['integer'])
                    ->width('6rem')
                    ->sortable(),
                TextColumn::make('posts_count')
                    ->counts('posts')
                    ->label('Số bài viết')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('menu_group')
                    ->label('Nhóm Menu')
                    ->options(Category::GROUP_LABELS),
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
