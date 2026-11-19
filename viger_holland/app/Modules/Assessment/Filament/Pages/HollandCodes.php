<?php

namespace App\Modules\Assessment\Filament\Pages;

use App\Filament\Pages\TabbedTablePage;
use App\Modules\Assessment\Models\CodeOverride;
use App\Modules\Assessment\Models\RiasecPair;
use App\Modules\Assessment\Models\RiasecType;
use App\Modules\Assessment\Models\ThirdLetter;
use App\Modules\Assessment\Services\ScoringEngine;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Làm gì:   Trang "Giải thích các mã Holland": sửa tên và đoạn giải thích khách đọc trong báo cáo, theo 4 thẻ:
 *           6 nhóm, 15 cặp (kèm ngành nền của cặp), 6 chữ thứ ba, mã 3 chữ có đoạn riêng (thêm / sửa / xóa).
 *           Mỗi lần lưu ghi vào lịch sử; báo cáo khách đọc ngay nội dung mới (báo cáo đã xem trước đó không đổi).
 * Ai gọi:   Filament (/admin/giai-thich-ma-holland). Quyền: catalog.manage.
 * Dữ liệu:  riasec_types, riasec_pairs + pair_major, third_letters, code_overrides.
 */
class HollandCodes extends TabbedTablePage
{
    protected static ?string $slug = 'giai-thich-ma-holland';

    protected static string $menuKey = 'codes';

    protected static function permission(): string
    {
        return 'catalog.manage';
    }

    public function tabs(): array
    {
        return [
            'nhom' => ['6 nhóm', 'Tên nhóm và đoạn khách đọc dưới "Nhóm nổi bật nhất" / "Nhóm thứ hai". Không nhắc thứ hạng trong đoạn, vì cùng một đoạn dùng cho cả hai vị trí.'],
            'cap' => ['15 cặp', 'Đoạn "Khi hai nhóm kết hợp" và các ngành nền của cặp (luôn được gợi ý trước). Cặp không có thứ tự: IS dùng chung cho IS và SI.'],
            'chu3' => ['Chữ thứ ba', 'Đoạn "Chữ thứ ba" (chỉ hiện khi bật "Cho khách thấy chữ thứ 3") và tên hướng chuyên sâu.'],
            'ma' => ['Mã 3 chữ riêng', 'Mã nào có đoạn riêng thì dùng đoạn này thay cho đoạn "Chữ thứ ba" chung. Không bắt buộc, chỉ viết cho mã hay gặp.'],
        ];
    }

    public function table(Table $table): Table
    {
        $authorize = fn () => static::canAccess();

        return match ($this->tab) {
            'cap' => $table
                ->query(RiasecPair::query()->with('majors')->orderBy('pair'))
                ->columns([
                    TextColumn::make('pair')->label('Cặp')->weight('bold'),
                    TextColumn::make('name')->label('Tên'),
                    TextColumn::make('relation')->label('Trên lục giác'),
                    TextColumn::make('description')->label('Khi hai nhóm kết hợp')->wrap()->lineClamp(3)->placeholder('Chưa có'),
                    TextColumn::make('majors.name_vi')->label('Ngành nền')->listWithLineBreaks()->placeholder('—'),
                ])
                ->recordActions([
                    EditAction::make()->authorize($authorize)->modalHeading(fn (RiasecPair $r) => 'Sửa cặp '.$r->pair)
                        ->schema([
                            TextInput::make('name')->label('Tên cặp')->required()->maxLength(150),
                            Textarea::make('description')->label('Đoạn "Khi hai nhóm kết hợp"')->rows(5)->maxLength(2000),
                            Select::make('majors')->label('Ngành nền của cặp')->multiple()->preload()
                                ->relationship('majors', 'name_vi', fn ($query) => $query->where('is_active', true))
                                ->helperText('Các ngành này luôn được gợi ý trước khi bật "Ưu tiên ngành nền của cặp mã".'),
                        ])
                        ->after(fn (Model $record) => $this->logChange($record, 'cặp '.$record->pair)),
                ])
                ->paginated(false),

            'chu3' => $table
                ->query(ThirdLetter::query()->orderByRaw($this->riasecOrder()))
                ->columns([
                    TextColumn::make('code')->label('Nhóm')->weight('bold'),
                    TextColumn::make('specialty_vi')->label('Hướng chuyên sâu')->placeholder('—'),
                    TextColumn::make('description')->label('Đoạn "Chữ thứ ba"')->wrap()->lineClamp(3)->placeholder('Chưa có'),
                ])
                ->recordActions([
                    EditAction::make()->authorize($authorize)->modalHeading(fn (ThirdLetter $r) => 'Sửa chữ thứ ba '.$r->code)
                        ->schema([
                            TextInput::make('specialty_vi')->label('Hướng chuyên sâu (tiếng Việt)')->maxLength(150),
                            TextInput::make('specialty_de')->label('Hướng chuyên sâu (tiếng Đức)')->maxLength(150),
                            TextInput::make('skills')->label('Kỹ năng nên tìm hiểu')->maxLength(255),
                            Textarea::make('description')->label('Đoạn "Chữ thứ ba"')->rows(4)->maxLength(2000),
                        ])
                        ->after(fn (Model $record) => $this->logChange($record, 'chữ thứ ba '.$record->code)),
                ])
                ->paginated(false),

            'ma' => $table
                ->query(CodeOverride::query()->orderBy('code'))
                ->columns([
                    TextColumn::make('code')->label('Mã')->weight('bold'),
                    TextColumn::make('interpretation')->label('Đoạn riêng')->wrap()->lineClamp(3),
                    TextColumn::make('note')->label('Ghi chú nội bộ')->placeholder('—'),
                ])
                ->emptyStateHeading('Chưa có mã nào có đoạn riêng')
                ->emptyStateDescription('Mọi mã 3 chữ đang dùng đoạn "Chữ thứ ba" chung. Bấm "Thêm mã" để viết đoạn riêng cho một mã.')
                ->headerActions([
                    CreateAction::make()->label('Thêm mã')->authorize($authorize)->modalHeading('Thêm đoạn riêng cho một mã 3 chữ')
                        ->schema($this->overrideSchema())
                        ->after(fn (Model $record) => $this->logChange($record, 'đoạn riêng của mã '.$record->code)),
                ])
                ->recordActions([
                    EditAction::make()->authorize($authorize)->schema($this->overrideSchema())
                        ->after(fn (Model $record) => $this->logChange($record, 'đoạn riêng của mã '.$record->code)),
                    DeleteAction::make()->authorize($authorize)->label('Xóa')->modalHeading(fn (CodeOverride $r) => 'Xóa đoạn riêng của mã '.$r->code.'?')
                        ->modalDescription('Mã này sẽ dùng lại đoạn "Chữ thứ ba" chung.'),
                ]),

            default => $table
                ->query(RiasecType::query()->orderBy('hex_index'))
                ->columns([
                    TextColumn::make('code')->label('Mã')->weight('bold'),
                    TextColumn::make('name')->label('Tên nhóm'),
                    TextColumn::make('label')->label('Tên đầy đủ')->placeholder('—'),
                    TextColumn::make('description')->label('Đoạn giải thích')->wrap()->lineClamp(3)->placeholder('Chưa có'),
                ])
                ->recordActions([
                    EditAction::make()->authorize($authorize)->modalHeading(fn (RiasecType $r) => 'Sửa nhóm '.$r->code)
                        ->schema([
                            TextInput::make('name')->label('Tên nhóm')->required()->maxLength(100)
                                ->helperText('Hiện ở mọi nơi: mã Holland, báo cáo, lục giác.'),
                            TextInput::make('label')->label('Tên đầy đủ')->maxLength(150),
                            Textarea::make('description')->label('Đoạn giải thích')->rows(5)->maxLength(2000),
                        ])
                        ->after(fn (Model $record) => $this->logChange($record, 'nhóm '.$record->code)),
                ])
                ->paginated(false),
        };
    }

    /**
     * @return list<mixed>
     */
    private function overrideSchema(): array
    {
        return [
            TextInput::make('code')->label('Mã 3 chữ')->required()->length(3)
                ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state))
                ->rules([
                    'regex:/^(?!.*(.).*\1)[RIASECriasec]{3}$/',
                    fn (?Model $record) => Rule::unique('code_overrides', 'code')->ignore($record?->getKey()),
                ])
                ->validationMessages(['regex' => 'Mã gồm 3 chữ khác nhau trong R, I, A, S, E, C, ví dụ ISA.', 'unique' => 'Mã này đã có đoạn riêng.'])
                ->helperText('Có thứ tự: ISA khác SIA.'),
            Textarea::make('interpretation')->label('Đoạn riêng (khách đọc)')->required()->rows(5)->maxLength(2000),
            Textarea::make('note')->label('Ghi chú nội bộ')->rows(2)->maxLength(1000),
        ];
    }

    private function riasecOrder(): string
    {
        $cases = collect(str_split(ScoringEngine::ORDER))->map(fn (string $g, int $i) => "WHEN '{$g}' THEN {$i}")->implode(' ');

        return "CASE code {$cases} END";
    }
}
