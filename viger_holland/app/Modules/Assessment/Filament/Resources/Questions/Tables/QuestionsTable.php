<?php

namespace App\Modules\Assessment\Filament\Resources\Questions\Tables;

use App\Modules\Assessment\Filament\Resources\Questions\QuestionResource;
use App\Modules\Assessment\Models\Question;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Làm gì:   Bảng danh sách câu hỏi: cột, bộ lọc (nhóm, chủ đề, loại, trạng thái, thùng rác), duyệt / tạm ngưng nhiều câu.
 * Ai gọi:   QuestionResource::table().
 * Dữ liệu:  bảng questions, question_facets.
 */
class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('facet'))
            ->defaultSort('code')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->columns([
                TextColumn::make('code')->label('Mã')->sortable()->searchable(),
                TextColumn::make('group')->label('Nhóm')->badge()->sortable()
                    ->formatStateUsing(fn (string $state) => QuestionResource::groupOptions()[$state] ?? $state),
                TextColumn::make('facet.name')->label('Chủ đề')->wrap()->toggleable()
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('facet', fn (Builder $facet) => $facet->where('name', 'like', "%{$search}%"))),
                TextColumn::make('text')->label('Câu hỏi')->wrap()->searchable()->grow(),
                TextColumn::make('priority_tier')->label('Loại')->badge()->sortable()
                    ->color(fn (string $state) => match ($state) {
                        'A' => 'success', 'B' => 'info', default => 'gray'
                    })
                    ->tooltip(fn (string $state) => Question::TIER_LABELS[$state] ?? null),
                TextColumn::make('selection_weight')->label('Trọng số')->numeric(decimalPlaces: 1)->sortable()->alignEnd(),
                TextColumn::make('review_status')->label('Trạng thái')->badge()->sortable()
                    ->formatStateUsing(fn (string $state) => Question::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Question::STATUS_APPROVED => 'success',
                        Question::STATUS_SUSPENDED => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')->label('Sửa lần cuối')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('group')->label('Nhóm')->options(fn () => QuestionResource::groupOptions()),
                SelectFilter::make('question_facet_id')->label('Chủ đề')->relationship('facet', 'name')->searchable()->preload(),
                SelectFilter::make('priority_tier')->label('Loại')->options(fn () => QuestionResource::tierOptions()),
                SelectFilter::make('review_status')->label('Trạng thái')->options(Question::STATUS_LABELS),
                TrashedFilter::make()->label('Thùng rác'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::statusBulkAction(Question::STATUS_APPROVED, 'Duyệt các câu đã chọn', Heroicon::OutlinedCheckCircle, 'success'),
                    self::statusBulkAction(Question::STATUS_SUSPENDED, 'Tạm ngưng các câu đã chọn', Heroicon::OutlinedPauseCircle, 'warning'),
                    DeleteBulkAction::make()->label('Cho vào thùng rác'),
                    RestoreBulkAction::make()->label('Khôi phục từ thùng rác'),
                ]),
            ]);
    }

    /**
     * Đổi trạng thái nhiều câu một lần (ví dụ tạm ngưng cả một chủ đề để Thầy xem lại).
     */
    private static function statusBulkAction(string $status, string $label, Heroicon $icon, string $color): BulkAction
    {
        return BulkAction::make('status_'.$status)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->modalDescription('Lượt làm bài mới chỉ rút câu "Đã duyệt". Lượt đã làm không bị ảnh hưởng.')
            ->authorize(fn () => auth()->user()?->can('update', new Question) ?? false)
            ->action(function (Collection $records) use ($status): void {
                $changed = Question::query()->whereKey($records->modelKeys())->where('review_status', '!=', $status)
                    ->update(['review_status' => $status, 'last_reviewed_at' => now(), 'updated_at' => now()]);

                Notification::make()->success()
                    ->title("Đã chuyển {$changed} câu sang \"".Question::STATUS_LABELS[$status].'"')
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
