<?php

namespace App\Modules\Schools\Filament\Pages;

use App\Filament\Pages\TabbedTablePage;
use App\Filament\Support\ImportActions;
use App\Models\AdminActivity;
use App\Models\User;
use App\Modules\Assessment\Models\Major;
use App\Modules\Schools\Models\Country;
use App\Modules\Schools\Models\Region;
use App\Modules\Schools\Models\School;
use App\Modules\Schools\Models\SchoolProgram;
use App\Modules\Schools\Services\ProgramImport;
use App\Modules\Schools\Services\SchoolImport;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

/**
 * Làm gì:   Trang "Trường & vùng": danh sách trường đại học (mặc định ẩn ĐH Hành chính / Đặc thù), ngành của từng trường
 *           (trường ↔ ngành, nhập được từ Excel), vùng (bang) và quốc gia.
 *           Tư vấn viên và quản trị viên xem (Vân chốt 17/11); chỉ người có quyền catalog.manage sửa, thêm và
 *           "Nhập từ Excel" (2 bước: tải file → ghép cột, máy tự đoán sẵn). Thiết kế mở: thêm nước khác là thêm dòng + nhập file.
 * Ai gọi:   Filament (/admin/truong-va-vung). Quyền: schools.view (xem), catalog.manage (sửa, nhập).
 * Dữ liệu:  countries, regions, schools, school_programs, majors (chỉ đọc); admin_activities.
 */
class SchoolsCatalog extends TabbedTablePage
{
    protected static ?string $slug = 'truong-va-vung';

    protected static string $menuKey = 'schools';

    /** Lọc sẵn thẻ "Ngành của trường" theo trường (nút "Ngành" ở dòng trường). */
    #[Url(as: 'truong')]
    public ?int $schoolFilter = null;

    /** Lọc sẵn thẻ "Ngành của trường" theo ngành (cột "Số trường" ở trang Ngành học). */
    #[Url(as: 'nganh')]
    public ?int $majorFilter = null;

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
            'chuong-trinh' => ['Ngành của trường', 'Trường nào dạy ngành nào (mỗi bậc một dòng). Lọc theo ngành để biết trường nào có ngành gợi ý cho khách.'],
            'vung' => ['Vùng / bang', 'Thông tin giới thiệu từng bang cho tư vấn viên.'],
            'quoc-gia' => ['Quốc gia', 'Thêm quốc gia mới rồi nhập danh sách trường của nước đó từ Excel.'],
        ];
    }

    public function table(Table $table): Table
    {
        $edit = fn () => static::canEdit();

        return match ($this->tab) {
            'chuong-trinh' => $this->programsTable($table, $edit),
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
                ->query(School::query()->with(['region', 'country'])->withCount('programs'))
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
                    ImportActions::import('import', 'trường', SchoolImport::class, 'catalog.truong', [
                        Select::make('country_id')->label('Quốc gia của các trường')->required()
                            ->options(fn () => Country::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                            ->default(fn () => Country::query()->orderBy('sort_order')->value('id')),
                    ], fn (array $data) => ['country' => Country::query()->findOrFail($data['country_id']), 'hidden_types' => self::HIDDEN_TYPES])
                        ->visible($edit)->authorize($edit),
                    ImportActions::template('schoolTemplate', 'mau-danh-sach-truong.xlsx', SchoolImport::class)->visible($edit)->authorize($edit),
                    CreateAction::make()->label('Thêm trường')->visible($edit)->authorize($edit)->schema($this->schoolSchema())
                        ->after(fn (Model $record) => $this->logChange($record, 'trường '.$record->name)),
                ])
                ->recordActions([
                    Action::make('programs')->label(fn (School $s) => 'Ngành ('.$s->programs_count.')')->icon(Heroicon::OutlinedAcademicCap)->color('gray')
                        ->url(fn (School $s) => static::getUrl(['the' => 'chuong-trinh', 'truong' => $s->id])),
                    EditAction::make()->visible($edit)->authorize($edit)->schema($this->schoolSchema())
                        ->after(fn (Model $record) => $this->logChange($record, 'trường '.$record->name)),
                ]),
        };
    }

    /**
     * Thẻ "Ngành của trường": mỗi dòng một trường + một ngành (+ bậc). Tư vấn viên xem; người có catalog.manage sửa, nhập Excel.
     */
    private function programsTable(Table $table, Closure $edit): Table
    {
        $log = fn (string $action, string $verb, SchoolProgram $record) => AdminActivity::record($action, 'catalog.chuong-trinh', $record->id,
            $verb.' ngành "'.$record->major?->name_vi.'" của trường '.$record->school?->name);
        $degree = fn (array $data) => ['degree' => (string) ($data['degree'] ?? '')] + $data;

        return $table
            ->query(SchoolProgram::query()->with(['school.region', 'major']))
            ->defaultSort('id', 'desc')
            ->searchable()
            ->searchPlaceholder('Tìm tên trường, ngành, chương trình')
            ->searchUsing(fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('program_name', 'like', "%{$search}%")
                ->orWhereHas('school', fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('short_name', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%"))
                ->orWhereHas('major', fn (Builder $q) => $q->where('name_vi', 'like', "%{$search}%")->orWhere('name_de', 'like', "%{$search}%"))))
            ->emptyStateHeading('Chưa có ngành nào được gắn với trường')
            ->emptyStateDescription('Bấm "Tải file mẫu", điền trường – ngành rồi "Nhập từ Excel", hoặc bấm "Thêm ngành cho trường".')
            ->columns([
                TextColumn::make('school.name')->label('Trường')->wrap()
                    ->description(fn (SchoolProgram $p) => collect([$p->school?->city, $p->school?->region?->name])->filter()->implode(' · ')),
                TextColumn::make('major.name_vi')->label('Ngành')->description(fn (SchoolProgram $p) => $p->major?->name_de)->wrap(),
                TextColumn::make('degree')->label('Bậc')->badge()->placeholder('Chưa rõ'),
                TextColumn::make('program_name')->label('Chương trình')->wrap()->placeholder('—')
                    ->url(fn (SchoolProgram $p) => $p->url, shouldOpenInNewTab: true),
                TextColumn::make('language')->label('Ngôn ngữ')->placeholder('—'),
                IconColumn::make('is_verified')->label('Đã kiểm tra')->boolean(),
            ])
            ->filters([
                SelectFilter::make('major_id')->label('Ngành')->searchable()->default($this->majorFilter)
                    ->options(fn () => Major::query()->orderBy('name_vi')->pluck('name_vi', 'id')->all()),
                SelectFilter::make('school_id')->label('Trường')->searchable()->default($this->schoolFilter)
                    ->options(fn () => School::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('region')->label('Vùng / bang')->searchable()
                    ->options(fn () => Region::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('school', fn (Builder $q) => $q->where('region_id', $data['value'])) : $query),
                SelectFilter::make('degree')->label('Bậc')
                    ->options(fn () => SchoolProgram::query()->where('degree', '!=', '')->distinct()->orderBy('degree')->pluck('degree', 'degree')->all()),
                TernaryFilter::make('is_verified')->label('Đã kiểm tra'),
            ])
            ->headerActions([
                ImportActions::import('importPrograms', 'trường – ngành', ProgramImport::class, 'catalog.chuong-trinh')->visible($edit)->authorize($edit),
                ImportActions::template('programTemplate', 'mau-truong-nganh.xlsx', ProgramImport::class)->visible($edit)->authorize($edit),
                CreateAction::make()->label('Thêm ngành cho trường')->visible($edit)->authorize($edit)->schema($this->programSchema())
                    ->mutateDataUsing($degree)
                    ->after(fn (SchoolProgram $record) => $log(AdminActivity::ACTION_CREATE, 'Thêm', $record)),
            ])
            ->recordActions([
                EditAction::make()->visible($edit)->authorize($edit)->schema($this->programSchema())
                    ->mutateDataUsing($degree)
                    ->after(fn (SchoolProgram $record) => $log(AdminActivity::ACTION_UPDATE, 'Sửa', $record)),
                DeleteAction::make()->label('Bỏ')->visible($edit)->authorize($edit)
                    ->modalHeading('Bỏ ngành này khỏi trường?')
                    ->before(fn (SchoolProgram $record) => $log(AdminActivity::ACTION_DELETE, 'Bỏ', $record)),
            ]);
    }

    /**
     * @return list<mixed>
     */
    private function programSchema(): array
    {
        return [
            Select::make('school_id')->label('Trường')->required()->searchable()
                ->options(fn () => School::query()->orderBy('name')->pluck('name', 'id')->all()),
            Select::make('major_id')->label('Ngành (trong danh mục)')->required()->searchable()
                ->options(fn () => Major::query()->orderBy('name_vi')->get()
                    ->mapWithKeys(fn (Major $m) => [$m->id => $m->name_vi.($m->name_de ? ' ('.$m->name_de.')' : '')])->all())
                ->helperText('Chưa có ngành: thêm ở "Ngành học & nghề gợi ý" trước.'),
            TextInput::make('degree')->label('Bậc')->maxLength(50)->datalist(['Bachelor', 'Master', 'Staatsexamen', 'Diplom'])
                ->rules([fn (Get $get, ?Model $record) => function (string $attribute, $value, $fail) use ($get, $record): void {
                    $exists = SchoolProgram::query()->where('school_id', $get('school_id'))->where('major_id', $get('major_id'))
                        ->where('degree', (string) $value)->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))->exists();
                    if ($exists) {
                        $fail('Trường này đã có ngành này ở bậc này.');
                    }
                }]),
            TextInput::make('program_name')->label('Tên chương trình tại trường')->maxLength(255)->placeholder('Informatik (B.Sc.)'),
            TextInput::make('language')->label('Ngôn ngữ dạy')->maxLength(50)->datalist(['Deutsch', 'Englisch', 'Deutsch / Englisch']),
            TextInput::make('study_form')->label('Hình thức học')->maxLength(50)->datalist(['Vollzeit', 'Dual', 'Teilzeit']),
            TextInput::make('url')->label('Link chương trình')->url()->maxLength(255),
            Toggle::make('is_verified')->label('Đã kiểm tra trên trang trường / Hochschulkompass'),
            Textarea::make('note')->label('Ghi chú')->rows(2),
        ];
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
