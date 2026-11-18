<?php

namespace App\Modules\Assessment\Filament\Pages;

use App\Filament\Pages\TabbedTablePage;
use App\Filament\Support\ImportActions;
use App\Modules\Assessment\Models\Major;
use App\Modules\Assessment\Models\Occupation;
use App\Modules\Assessment\Services\MajorImport;
use App\Modules\Schools\Filament\Pages\SchoolsCatalog;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Trang "Ngành học & nghề gợi ý": thẻ Ngành (tên Việt / Đức, mã Holland của ngành, hệ đào tạo, link nguồn chính thức,
 *           dấu "Đã kiểm tra", đang dùng / ngừng dùng, nghề liên quan, số trường có ngành → bấm để xem trường) và thẻ Nghề
 *           (tên Việt / Đức / Anh). Thêm mới tự đặt mã (MAJ…, OCC…). Ngành nhập / cập nhật được từ Excel (có file mẫu).
 *           Không xóa: ngừng dùng thì không gợi ý nữa nhưng lượt làm bài cũ vẫn đọc được.
 *           Ngành "Đã kiểm tra" phải có link nguồn (Vân chốt 17/11: ngành có thật ở Đức, theo nguồn chính thức).
 * Ai gọi:   Filament (/admin/nganh-hoc-va-nghe). Quyền: catalog.manage.
 * Dữ liệu:  majors, occupations, major_occupation (nghề liên quan), school_programs (đếm số trường).
 */
class MajorsCatalog extends TabbedTablePage
{
    protected static ?string $slug = 'nganh-hoc-va-nghe';

    protected static string $menuKey = 'majors';

    protected static function permission(): string
    {
        return 'catalog.manage';
    }

    public function tabs(): array
    {
        return [
            'nganh' => ['Ngành học', 'Bật "Chỉ gợi ý ngành đã kiểm tra nguồn chính thức" ở "Cách bốc đề & chấm điểm" thì khách chỉ thấy ngành có dấu "Đã kiểm tra".'],
            'nghe' => ['Nghề', 'Tên nghề hiện ở báo cáo khách dạng "Tiếng Việt (Tiếng Đức)".'],
        ];
    }

    public function table(Table $table): Table
    {
        $authorize = fn () => static::canAccess();

        if ($this->tab === 'nghe') {
            return $table
                ->query(Occupation::query())
                ->defaultSort('code')
                ->columns([
                    TextColumn::make('code')->label('Mã')->sortable(),
                    TextColumn::make('name_vi')->label('Tên tiếng Việt')->searchable()->placeholder('Chưa có'),
                    TextColumn::make('name_de')->label('Tên tiếng Đức')->searchable(),
                    TextColumn::make('name_en')->label('Tên tiếng Anh')->toggleable(isToggledHiddenByDefault: true),
                    IconColumn::make('is_active')->label('Đang dùng')->boolean(),
                ])
                ->filters([TernaryFilter::make('is_active')->label('Đang dùng')])
                ->headerActions([
                    CreateAction::make()->label('Thêm nghề')->authorize($authorize)->schema($this->occupationSchema())
                        ->mutateDataUsing(fn (array $data) => $data + ['code' => $this->nextCode(Occupation::class, 'OCC')])
                        ->after(fn (Model $record) => $this->logChange($record, 'nghề '.$record->code)),
                ])
                ->recordActions([
                    EditAction::make()->authorize($authorize)->schema($this->occupationSchema())
                        ->after(fn (Model $record) => $this->logChange($record, 'nghề '.$record->code)),
                ]);
        }

        return $table
            ->query(Major::query()->withCount(['programs', 'occupationLinks']))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('code')->label('Mã')->sortable(),
                TextColumn::make('name_vi')->label('Ngành')->description(fn (Major $m) => $m->name_de)->searchable(['name_vi', 'name_de'])->wrap(),
                TextColumn::make('holland_codes')->label('Mã Holland')->badge()->placeholder('Chưa có'),
                TextColumn::make('routes')->label('Hệ đào tạo')->placeholder('—'),
                TextColumn::make('occupation_links_count')->label('Nghề')->alignCenter(),
                TextColumn::make('programs_count')->label('Số trường')->alignCenter()->color('primary')
                    ->tooltip('Bấm để xem trường có ngành này')
                    ->url(fn (Major $m) => SchoolsCatalog::getUrl(['the' => 'chuong-trinh', 'nganh' => $m->id])),
                IconColumn::make('is_verified')->label('Đã kiểm tra')->boolean(),
                IconColumn::make('is_active')->label('Đang dùng')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_verified')->label('Đã kiểm tra nguồn'),
                TernaryFilter::make('is_active')->label('Đang dùng'),
            ])
            ->headerActions([
                ImportActions::import('importMajors', 'ngành học', MajorImport::class, 'catalog.nganh-hoc')->authorize($authorize),
                ImportActions::template('majorTemplate', 'mau-nganh-hoc.xlsx', MajorImport::class)->authorize($authorize),
                CreateAction::make()->label('Thêm ngành')->authorize($authorize)->schema($this->majorSchema())
                    ->mutateDataUsing(fn (array $data) => $data + [
                        'code' => MajorImport::nextCode(),
                        'sort_order' => (int) Major::withTrashed()->max('sort_order') + 1,
                    ])
                    ->after(fn (Model $record) => $this->logChange($record, 'ngành '.$record->code)),
            ])
            ->recordActions([
                EditAction::make()->authorize($authorize)->schema($this->majorSchema())
                    ->after(fn (Model $record) => $this->logChange($record, 'ngành '.$record->code)),
            ]);
    }

    /**
     * @return list<mixed>
     */
    private function majorSchema(): array
    {
        return [
            TextInput::make('name_vi')->label('Tên tiếng Việt')->required()->maxLength(150),
            TextInput::make('name_de')->label('Tên tiếng Đức')->maxLength(150),
            TextInput::make('name_en')->label('Tên tiếng Anh')->maxLength(150),
            TagsInput::make('holland_codes')->label('Mã Holland của ngành')->placeholder('Gõ mã rồi bấm Enter, ví dụ ISC')
                ->nestedRecursiveRules(['regex:/^(?!.*(.).*\1)[RIASEC]{3}$/'])
                ->helperText('1–3 mã, mỗi mã 3 chữ khác nhau trong R I A S E C. Ngành chưa có mã thì không được gợi ý (trừ ngành nền của cặp).'),
            TextInput::make('routes')->label('Hệ đào tạo')->maxLength(100)->placeholder('Uni, HAW'),
            TextInput::make('source_url')->label('Link nguồn chính thức')->url()->maxLength(255)
                ->placeholder('https://www.hochschulkompass.de/…')
                ->helperText('Hochschulkompass, studienwahl.de hoặc trang chính thức của trường.'),
            Toggle::make('is_verified')->label('Đã kiểm tra: ngành có thật ở Đức, theo nguồn chính thức')
                ->rules([fn (Get $get) => fn (string $attribute, $value, $fail) => $value && blank($get('source_url')) ? $fail('Nhập link nguồn chính thức trước khi đánh dấu đã kiểm tra.') : null]),
            Toggle::make('is_active')->label('Đang dùng (được gợi ý)')->default(true),
            Repeater::make('occupationLinks')->relationship()->label('Nghề liên quan (hiện ở báo cáo, tối đa 3 nghề đầu)')
                ->addActionLabel('Thêm nghề')->defaultItems(0)->columns(2)->reorderable(false)
                ->components([
                    Select::make('occupation_id')->label('Nghề')->required()->searchable()->distinct()
                        ->options(fn () => Occupation::query()->orderBy('name_vi')->get()
                            ->mapWithKeys(fn (Occupation $o) => [$o->id => ($o->name_vi ?: $o->name_de).($o->name_vi && $o->name_de ? ' ('.$o->name_de.')' : '')])->all())
                        ->helperText('Chưa có nghề: thêm ở thẻ "Nghề" trước.'),
                    TextInput::make('link_type')->label('Loại liên kết')->maxLength(100)->datalist(['Trực tiếp', 'Liên quan', 'Mở rộng']),
                ]),
        ];
    }

    /**
     * @return list<mixed>
     */
    private function occupationSchema(): array
    {
        return [
            TextInput::make('name_vi')->label('Tên tiếng Việt')->required()->maxLength(150),
            TextInput::make('name_de')->label('Tên tiếng Đức')->maxLength(150),
            TextInput::make('name_en')->label('Tên tiếng Anh')->maxLength(150),
            Toggle::make('is_active')->label('Đang dùng')->default(true),
        ];
    }

    /**
     * Mã kế tiếp, ví dụ MAJ047, OCC052.
     *
     * @param  class-string<Model>  $model
     */
    private function nextCode(string $model, string $prefix): string
    {
        $max = $model::query()->withTrashed()->where('code', 'like', $prefix.'%')->pluck('code')
            ->map(fn (string $code) => (int) substr($code, strlen($prefix)))->max() ?? 0;

        return $prefix.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
