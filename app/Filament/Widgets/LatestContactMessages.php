<?php

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestContactMessages extends TableWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Liên hệ gần đây';

    public function table(Table $table): Table
    {
        return $table
            ->query(ContactMessage::query()->latest('created_at')->limit(5))
            ->paginated(false)
            ->emptyStateHeading('Chưa có liên hệ nào')
            ->emptyStateIcon('heroicon-o-inbox')
            ->columns([
                TextColumn::make('name')
                    ->label('Người gửi')
                    ->weight('medium')
                    ->description(fn (ContactMessage $record) => $record->phone),
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i'),
            ]);
    }
}
