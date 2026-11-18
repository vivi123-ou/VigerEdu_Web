<?php

namespace App\Modules\Content\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Filament\Support\ContentFields;
use App\Models\Media;
use App\Support\AdminMenu;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * Làm gì:   Trang "Kho ảnh": tải nhiều ảnh lên (tự thu nhỏ còn rộng 1920px, nén), xem dạng lưới, chép link,
 *           xóa vào thùng rác và khôi phục. Trang nào đang dùng ảnh đã xóa vẫn hiện bình thường.
 * Ai gọi:   Filament (/admin/kho-anh). Quyền: content.manage.
 * Dữ liệu:  bảng media, file trong storage/app/public/media (qua App\Services\ImageUploader).
 */
class MediaLibrary extends Page implements HasTable
{
    use InteractsWithTable;
    use UsesAdminMenu;

    protected static ?string $slug = 'kho-anh';

    protected static string $menuKey = 'media';

    protected string $view = 'filament.pages.simple-table';

    public static function canAccess(): bool
    {
        return AdminMenu::allows(auth()->user(), static::$menuKey);
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return static::menuItem()['summary'] ?? null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Chọn ảnh')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading('Tải ảnh lên')
                ->modalSubmitActionLabel('Tải lên')
                ->authorize(fn () => static::canAccess())
                ->schema([
                    FileUpload::make('files')->label('Ảnh')->multiple()->maxFiles(20)->image()->required()->storeFiles(false)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])->maxSize(8192)
                        ->helperText('Chọn được nhiều ảnh cùng lúc. JPG, PNG, WebP hoặc GIF, mỗi ảnh tối đa 8 MB. Banner nên rộng 1600–1920px; ảnh rộng hơn 1920px được tự thu nhỏ.'),
                ])
                ->action(function (array $data): void {
                    $saved = 0;
                    foreach ((array) $data['files'] as $file) {
                        try {
                            ContentFields::upload($file);
                            $saved++;
                        } catch (RuntimeException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();
                        }
                    }
                    if ($saved > 0) {
                        Notification::make()->success()->title('Đã tải lên '.$saved.' ảnh')->send();
                    }
                }),
            static::helpAction(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Media::query())
            ->defaultSort('id', 'desc')
            ->contentGrid(['sm' => 2, 'md' => 3, 'xl' => 4])
            ->paginated([24, 48])
            ->searchable()
            ->searchPlaceholder('Tìm theo tên ảnh')
            ->searchUsing(fn (Builder $query, string $search) => $query->where('original_name', 'like', '%'.$search.'%'))
            ->emptyStateHeading('Chưa có ảnh nào')
            ->emptyStateDescription('Bấm "Chọn ảnh" ở góc trên để tải ảnh lên.')
            ->columns([
                Stack::make([
                    ImageColumn::make('path')->label('Ảnh')
                        ->state(fn (Media $record) => $record->url())
                        ->imageHeight(140)->imageWidth('100%')
                        ->extraImgAttributes(['style' => 'object-fit:cover;border-radius:.5rem', 'loading' => 'lazy']),
                    TextColumn::make('original_name')->label('Tên')->weight('medium')->limit(40)
                        ->tooltip(fn (Media $record) => $record->original_name),
                    TextColumn::make('size')->label('Cỡ')->color('gray')->size('sm')
                        ->formatStateUsing(fn (Media $record) => $record->width.'×'.$record->height.' · '.$record->sizeLabel()),
                    TextColumn::make('link')->label('Link')->color('gray')->size('sm')
                        ->state(fn (Media $record) => $record->url())
                        ->formatStateUsing(fn () => 'Chép link ảnh')
                        ->icon(Heroicon::OutlinedClipboardDocument)
                        ->copyable()->copyableState(fn (Media $record) => $record->url())->copyMessage('Đã chép link'),
                ])->space(2),
            ])
            ->filters([
                TrashedFilter::make()->label('Thùng rác')
                    ->placeholder('Ảnh đang dùng')->trueLabel('Cả ảnh trong thùng rác')->falseLabel('Chỉ ảnh trong thùng rác'),
            ])
            ->recordActions([
                DeleteAction::make()->label('Xóa')
                    ->modalHeading('Đưa ảnh này vào thùng rác?')
                    ->modalDescription('Trang nào đang dùng ảnh này vẫn hiện bình thường. Lấy lại được ở bộ lọc "Thùng rác".')
                    ->successNotificationTitle('Đã đưa ảnh vào thùng rác')
                    ->authorize(fn () => static::canAccess()),
                RestoreAction::make()->label('Khôi phục')
                    ->successNotificationTitle('Đã khôi phục ảnh')
                    ->authorize(fn () => static::canAccess()),
            ]);
    }
}
