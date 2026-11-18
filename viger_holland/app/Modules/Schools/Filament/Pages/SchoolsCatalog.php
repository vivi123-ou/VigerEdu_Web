<?php

namespace App\Modules\Schools\Filament\Pages;

use App\Filament\Pages\TabbedTablePage;
use App\Models\AdminActivity;
use App\Models\User;
use App\Modules\Schools\Models\Country;
use App\Modules\Schools\Models\Region;
use App\Modules\Schools\Models\School;
use App\Modules\Schools\Services\SchoolImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Làm gì:   Trang "Trường & vùng": danh sách trường đại học (mặc định ẩn ĐH Hành chính / Đặc thù), vùng (bang) và quốc gia.
 *           Tư vấn viên và quản trị viên xem (Vân chốt 17/11); chỉ người có quyền catalog.manage sửa, thêm và
 *           "Nhập từ Excel" (2 bước: tải file → ghép cột, máy tự đoán sẵn). Thiết kế mở: thêm nước khác là thêm dòng + nhập file.
 * Ai gọi:   Filament (/admin/truong-va-vung). Quyền: schools.view (xem), catalog.manage (sửa, nhập).
 * Dữ liệu:  countries, regions, schools; admin_activities.
 */
class SchoolsCatalog extends TabbedTablePage
{
    protected static ?string $slug = 'truong-va-vung';

    protected static string $menuKey = 'schools';

    /** Loại hình mặc định ẩn khi nhập trường mới. */
    private const HIDDEN_TYPES = ['ĐH Hành chính', 'ĐH Đặc thù'];

    protected static function permission(): string
    {
        return 'schools.view';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && ($user->hasPermission('schools.view') || $user->hasPermission('catalog.manage'));
    }

    public static function canEdit(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && $user->hasPermission('catalog.manage');
    }

    public function tabs(): array
    {
        return [
            'truong' => ['Trường', 'Trường đang ẩn (ví dụ ĐH Hành chính, ĐH Đặc thù) không hiện; chọn bộ lọc "Đang ẩn" để xem.'],
            'vung' => ['Vùng / bang', 'Thông tin giới thiệu từng bang cho tư vấn viên.'],
            'quoc-gia' => ['Quốc gia', 'Thêm quốc gia mới rồi nhập danh sách trường của nước đó từ Excel.'],
        ];
    }

    public function table(Table $table): Table
    {
        $edit = fn () => static::canEdit();

        return match ($this->tab) {
            'vung' => $table
                ->query(Region::query()->with('country')->withCount('schools'))
                ->defaultSort('name')
                ->columns([
                    TextColumn::make('name')->label('Vùng / bang')->description(fn (Region $r) => $r->name_alt)->searchable(['name', 'name_alt']),
                    TextColumn::make('code')->label('Mã')->placeholder('—'),
                    TextColumn::make('country.name')->label('Quốc gia'),
                    TextColumn::make('capital')->label('Thủ phủ')->placeholder('—'),
                    TextColumn::make('schools_count')->label('Số trường'),
                    TextColumn::make('highlights')->label('Lĩnh vực nổi bật')->wrap()->lineClamp(2)->placeholder('—'),
                ])
                ->recordActions([
                    EditAction::make()->visible($edit)->authorize($edit)->schema([
                        TextInput::make('name')->label('Tên')->required()->maxLength(150),
                        TextInput::make('name_alt')->label('Tên khác')->maxLength(150),
                        TextInput::make('code')->label('Mã')->maxLength(10),
                        TextInput::make('capital')->label('Thủ phủ')->maxLength(150),
                        Textarea::make('highlights')->label('Lĩnh vực nổi bật')->rows(2),
                        Textarea::make('description')->label('Mô tả')->rows(4),
                    ])->after(fn (Model $record) => $this->logChange($record, 'vùng '.$record->name)),
                ]),

            'quoc-gia' => $table
                ->query(Country::query()->withCount(['regions', 'schools']))
                ->defaultSort('sort_order')
                ->columns([
                    TextColumn::make('code')->label('Mã'),
                    TextColumn::make('name')->label('Quốc gia'),
                    TextColumn::make('regions_count')->label('Số vùng'),
                    TextColumn::make('schools_count')->label('Số trường'),
                ])
                ->headerActions([
                    CreateAction::make()->label('Thêm quốc gia')->visible($edit)->authorize($edit)->schema($this->countrySchema())
                        ->after(fn (Model $record) => $this->logChange($record, 'quốc gia '.$record->name)),
                ])
                ->recordActions([
                    EditAction::make()->visible($edit)->authorize($edit)->schema($this->countrySchema())
                        ->after(fn (Model $record) => $this->logChange($record, 'quốc gia '.$record->name)),
                ]),

            default => $table
                ->query(School::query()->with(['region', 'country']))
                ->defaultSort('name')
                ->columns([
                    TextColumn::make('name')->label('Trường')->description(fn (School $s) => $s->short_name)->searchable(['name', 'short_name', 'display_name', 'city'])->wrap(),
                    TextColumn::make('type')->label('Loại hình')->badge()->placeholder('—'),
                    TextColumn::make('ownership')->label('Hình thức')->placeholder('—'),
                    TextColumn::make('region.name')->label('Vùng / bang')->placeholder('—'),
                    TextColumn::make('city')->label('Thành phố')->placeholder('—'),
                    TextColumn::make('students')->label('Sinh viên')->numeric(thousandsSeparator: '.')->sortable()->placeholder('—'),
                    TextColumn::make('website')->label('Trang web')->url(fn (School $s) => $s->website, shouldOpenInNewTab: true)->limit(30)->placeholder('—'),
                    IconColumn::make('is_hidden')->label('Đang ẩn')->boolean()->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    SelectFilter::make('country_id')->label('Quốc gia')->relationship('country', 'name'),
                    SelectFilter::make('region_id')->label('Vùng / bang')->relationship('region', 'name')->searchable()->preload(),
                    SelectFilter::make('type')->label('Loại hình')
                        ->options(fn () => School::query()->whereNotNull('type')->distinct()->orderBy('type')->pluck('type', 'type')->all()),
                    SelectFilter::make('ownership')->label('Hình thức')
                        ->options(fn () => School::query()->whereNotNull('ownership')->distinct()->orderBy('ownership')->pluck('ownership', 'ownership')->all()),
                    TernaryFilter::make('is_hidden')->label('Trường đang ẩn')->default(false)
                        ->trueLabel('Chỉ trường đang ẩn')->falseLabel('Trường đang hiện')->placeholder('Tất cả'),
                ])
                ->headerActions([
                    $this->importAction()->visible($edit)->authorize($edit),
                    CreateAction::make()->label('Thêm trường')->visible($edit)->authorize($edit)->schema($this->schoolSchema())
                        ->after(fn (Model $record) => $this->logChange($record, 'trường '.$record->name)),
                ])
                ->recordActions([
                    EditAction::make()->visible($edit)->authorize($edit)->schema($this->schoolSchema())
                        ->after(fn (Model $record) => $this->logChange($record, 'trường '.$record->name)),
                ]),
        };
    }

    private function importAction(): Action
    {
        return Action::make('import')->label('Nhập từ Excel')->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
            ->modalHeading('Nhập danh sách trường từ Excel')
            ->steps([
                Step::make('Tải file')->description('File Excel (.xlsx) hoặc .csv, dòng đầu là tên cột.')->schema([
                    Select::make('country_id')->label('Quốc gia của các trường')->options(fn () => Country::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                        ->default(fn () => Country::query()->orderBy('sort_order')->value('id'))->required(),
                    FileUpload::make('file')->label('File')->disk('local')->directory('imports')->required()
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'text/plain'])
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $file = is_array($state) ? reset($state) : $state;
                            if (! $file instanceof TemporaryUploadedFile) {
                                return;
                            }
                            $import = app(SchoolImport::class);
                            $headers = $import->headers($file->getRealPath());
                            $set('headers', $headers);
                            foreach ($import->guess($headers) as $field => $column) {
                                $set('map.'.$field, $column);
                            }
                        }),
                    Hidden::make('headers'),
                ]),
                Step::make('Ghép cột')->description('Chọn cột trong file ứng với từng ô. Máy đã đoán sẵn, bạn kiểm tra lại. Cột không chọn được lưu ở phần thêm.')
                    ->schema(fn (Get $get) => collect(School::IMPORTABLE)->map(fn (string $label, string $field) => Select::make('map.'.$field)
                        ->label($label)->required($field === 'name')
                        ->options(collect($get('headers') ?? [])->filter()->mapWithKeys(fn ($h) => [$h => $h])->all()))
                        ->values()->all())->columns(2),
            ])
            ->action(function (array $data): void {
                $path = Storage::disk('local')->path((string) $data['file']);
                $stats = app(SchoolImport::class)->import($path, Country::query()->findOrFail($data['country_id']), $data['map'] ?? [], self::HIDDEN_TYPES);
                Storage::disk('local')->delete((string) $data['file']);
                AdminActivity::record(AdminActivity::ACTION_IMPORT, 'catalog.truong', null,
                    "Nhập trường từ Excel: thêm {$stats['created']}, cập nhật {$stats['updated']}, bỏ qua {$stats['skipped']}");
                Notification::make()->success()->title('Đã nhập xong')
                    ->body("Thêm {$stats['created']} trường, cập nhật {$stats['updated']}, bỏ qua {$stats['skipped']} dòng không có tên trường.")->send();
            });
    }

    /**
     * @return list<mixed>
     */
    private function schoolSchema(): array
    {
        return [
            Select::make('country_id')->label('Quốc gia')->relationship('country', 'name')->required()
                ->default(fn () => Country::query()->orderBy('sort_order')->value('id'))->live(),
            Select::make('region_id')->label('Vùng / bang')
                ->options(fn (Get $get) => Region::query()->where('country_id', $get('country_id'))->orderBy('name')->pluck('name', 'id')->all()),
            TextInput::make('name')->label('Tên trường')->required()->maxLength(255),
            TextInput::make('short_name')->label('Tên viết tắt')->maxLength(100),
            TextInput::make('type')->label('Loại hình')->maxLength(100)->datalist(fn () => School::query()->distinct()->pluck('type')->filter()->all()),
            TextInput::make('ownership')->label('Hình thức')->maxLength(100)->datalist(fn () => School::query()->distinct()->pluck('ownership')->filter()->all()),
            TextInput::make('city')->label('Thành phố')->maxLength(150),
            TextInput::make('website')->label('Trang web')->url()->maxLength(255),
            TextInput::make('students')->label('Số sinh viên')->integer()->minValue(0),
            Toggle::make('is_hidden')->label('Ẩn khỏi danh sách tư vấn'),
        ];
    }

    /**
     * @return list<mixed>
     */
    private function countrySchema(): array
    {
        return [
            TextInput::make('code')->label('Mã quốc gia (2 chữ, ví dụ AT)')->required()->length(2)
                ->dehydrateStateUsing(fn (?string $s) => strtoupper((string) $s))
                ->unique(Country::class, 'code', ignoreRecord: true),
            TextInput::make('name')->label('Tên')->required()->maxLength(100),
        ];
    }
}
