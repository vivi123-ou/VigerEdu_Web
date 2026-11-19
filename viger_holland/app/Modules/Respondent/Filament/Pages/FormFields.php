<?php

namespace App\Modules\Respondent\Filament\Pages;

use App\Filament\Pages\TabbedTablePage;
use App\Modules\Respondent\Models\FormField;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Làm gì:   Trang "Form khách điền thông tin": các ô của form nhận báo cáo (kéo để đổi thứ tự), sửa chữ, bắt buộc, ẩn / hiện,
 *           gửi CRM hay không, "Tên trường bên CRM" (chọn trong danh sách theo từ điển chung; không chọn thì đi vào extra_fields),
 *           lựa chọn của ô chọn. Thêm ô mới; ô lõi (họ tên, email, SĐT…) không xóa được; ô Email luôn bắt buộc vì mã xác nhận
 *           gửi qua email. Xóa ô thường thì vào thùng rác, khôi phục được; giá trị khách đã điền vẫn giữ.
 * Ai gọi:   Filament (/admin/form-khach-dien). Quyền: form_fields.manage.
 * Dữ liệu:  form_fields; config/crm.php (danh sách tên trường bên CRM).
 */
class FormFields extends TabbedTablePage
{
    protected static ?string $slug = 'form-khach-dien';

    protected static string $menuKey = 'form-fields';

    /** Ô mà luồng khách phụ thuộc: luôn hiện và bắt buộc. */
    private const LOCKED = ['email'];

    protected static function permission(): string
    {
        return 'form_fields.manage';
    }

    public function tabs(): array
    {
        return [
            'cac-o' => ['Các ô của form', 'Kéo biểu tượng ở đầu dòng để đổi thứ tự. Sửa xong, khách mở form là thấy ngay.'],
            'thung-rac' => ['Thùng rác', 'Ô đã xóa. Khôi phục thì ô hiện lại trên form.'],
        ];
    }

    public function table(Table $table): Table
    {
        $authorize = fn () => static::canAccess();

        if ($this->tab === 'thung-rac') {
            return $table
                ->query(FormField::onlyTrashed())
                ->columns([
                    TextColumn::make('label')->label('Ô'),
                    TextColumn::make('deleted_at')->label('Xóa lúc')->dateTime('H:i d/m/Y'),
                ])
                ->recordActions([
                    Action::make('restore')->label('Khôi phục')->icon(Heroicon::OutlinedArrowUturnLeft)->authorize($authorize)
                        ->action(function (FormField $record): void {
                            $record->restore();
                            $this->logChange($record, 'ô "'.$record->label.'" (khôi phục)');
                            Notification::make()->success()->title('Đã khôi phục ô "'.$record->label.'"')->send();
                        }),
                ]);
        }

        return $table
            ->query(FormField::query())
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->paginated(false)
            ->columns([
                TextColumn::make('label')->label('Ô')->description(fn (FormField $f) => $f->help)->wrap(),
                TextColumn::make('type')->label('Kiểu')->formatStateUsing(fn (string $state) => FormField::TYPES[$state] ?? $state),
                IconColumn::make('is_required')->label('Bắt buộc')->boolean(),
                IconColumn::make('is_visible')->label('Đang hiện')->boolean(),
                IconColumn::make('to_crm')->label('Gửi CRM')->boolean(),
                TextColumn::make('crm_key')->label('Tên trường bên CRM')->placeholder('Trường mở rộng'),
            ])
            ->headerActions([
                CreateAction::make()->label('Thêm ô')->authorize($authorize)->modalHeading('Thêm ô vào form')
                    ->schema($this->fieldSchema(null))
                    ->mutateDataUsing(fn (array $data) => $data + [
                        'key' => $this->newKey((string) $data['label']),
                        'is_system' => false,
                        'sort_order' => (int) FormField::withTrashed()->max('sort_order') + 1,
                    ])
                    ->after(fn (Model $record) => $this->logChange($record, 'ô "'.$record->label.'"')),
            ])
            ->recordActions([
                EditAction::make()->authorize($authorize)->modalHeading(fn (FormField $f) => 'Sửa ô "'.$f->label.'"')
                    ->schema(fn (FormField $record) => $this->fieldSchema($record))
                    ->mutateDataUsing(fn (array $data, FormField $record) => in_array($record->key, self::LOCKED, true)
                        ? ['is_required' => true, 'is_visible' => true] + $data : $data)
                    ->after(fn (Model $record) => $this->logChange($record, 'ô "'.$record->label.'"')),
                DeleteAction::make()->label('Xóa')->authorize($authorize)
                    ->hidden(fn (FormField $record) => $record->is_system)
                    ->modalHeading(fn (FormField $f) => 'Xóa ô "'.$f->label.'"?')
                    ->modalDescription('Ô vào thùng rác, khôi phục được. Giá trị khách đã điền trước đây vẫn giữ.'),
            ]);
    }

    /**
     * @return list<mixed>
     */
    private function fieldSchema(?FormField $field): array
    {
        $locked = $field !== null && in_array($field->key, self::LOCKED, true);
        $crmKeys = collect(array_keys(config('crm.paths', [])))->mapWithKeys(fn (string $key) => [$key => $key])->all();

        return [
            TextInput::make('label')->label('Chữ trên ô')->required()->maxLength(255),
            Select::make('type')->label('Kiểu ô')->options(FormField::TYPES)->required()->default('text')->live()
                ->disabled($field?->is_system ?? false)
                ->helperText($field?->is_system ? 'Ô lõi: không đổi kiểu được.' : null),
            Repeater::make('options')->label('Các lựa chọn')->columns(2)->addActionLabel('Thêm lựa chọn')->defaultItems(2)
                ->visible(fn (Get $get) => in_array($get('type'), ['select', 'radio'], true))
                ->schema([
                    TextInput::make('value')->label('Mã lưu (không dấu)')->required()->maxLength(60)->alphaDash(),
                    TextInput::make('label')->label('Chữ khách thấy')->required()->maxLength(150),
                ]),
            TextInput::make('placeholder')->label('Chữ mờ gợi ý trong ô')->maxLength(150),
            TextInput::make('help')->label('Ghi chú cho người quản trị')->maxLength(500),
            Toggle::make('is_required')->label('Bắt buộc')->disabled($locked)
                ->helperText($locked ? 'Mã xác nhận gửi qua email nên ô này luôn bắt buộc.' : null),
            Toggle::make('is_visible')->label('Hiện trên form')->default(true)->disabled($locked),
            Toggle::make('to_crm')->label('Gửi sang CRM')->default(true),
            Select::make('crm_key')->label('Tên trường bên CRM')->options($crmKeys)->placeholder('Trường mở rộng (extra_fields)')
                ->helperText('Theo từ điển dữ liệu chung. Không chọn thì CRM nhận ở extra_fields theo mã của ô.')
                ->disabled($field?->is_system ?? false),
        ];
    }

    /**
     * Mã ô từ chữ: "Học trường chuyên?" → hoc_truong_chuyen (thêm _2, _3 nếu trùng).
     */
    private function newKey(string $label): string
    {
        $base = Str::limit(Str::slug($label, '_'), 50, '') ?: 'o_moi';
        $key = $base;
        for ($i = 2; FormField::withTrashed()->where('key', $key)->exists(); $i++) {
            $key = $base.'_'.$i;
        }

        return $key;
    }
}
