<?php

namespace App\Modules\Assessment\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Models\User;
use App\Modules\Assessment\Models\TestConfigVersion;
use App\Modules\Assessment\Support\TestConfig;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

/**
 * Làm gì:   Trang "Cách bốc đề & chấm điểm": mọi con số của rút đề, trang làm bài, chấm điểm, gợi ý ngành, cảnh báo.
 *           Mỗi lần lưu là một phiên bản mới (ghi rõ ô nào đổi); "Quay lại bản này" tạo phiên bản mới từ bản cũ.
 * Ai gọi:   Filament (đường dẫn /admin/test-settings). Quyền: test_settings.manage.
 * Dữ liệu:  bảng test_config_versions (qua TestConfig).
 */
class TestSettings extends Page implements HasTable
{
    use InteractsWithTable;
    use UsesAdminMenu;

    protected static ?string $slug = 'test-settings';

    protected static string $menuKey = 'settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && $user->hasPermission('test_settings.manage');
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
        return [static::helpAction()];
    }

    public function mount(): void
    {
        $this->form->fill(TestConfig::current());
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];

        foreach (TestConfig::FIELDS as $title => $fields) {
            $sections[] = Section::make($title)
                ->columns(2)
                ->collapsible()
                ->collapsed(str_starts_with($title, 'Nâng cao'))
                ->components(collect($fields)->map(fn (array $field, string $key) => $this->field($key, $field))->values()->all());
        }

        return $schema->components($sections)->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Lưu')->submit('save')->keyBindings(['mod+s']),
                    ])->sticky(),
                ]),
            Section::make('Lịch sử thay đổi')
                ->description('Bản mới nhất là bản đang dùng. Quay lại bản cũ cũng lưu thành một phiên bản mới, không xóa lịch sử.')
                ->components([EmbeddedTable::make()]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $input = $this->form->getState();
        $validator = TestConfig::validator($input);

        if ($validator->fails()) {
            throw ValidationException::withMessages(
                collect($validator->errors()->messages())->mapWithKeys(fn (array $messages, string $key) => ['data.'.$key => $messages])->all(),
            );
        }

        $before = TestConfig::current();
        $after = TestConfig::normalize($input);
        $version = TestConfig::save($after, auth()->user(), TestConfig::describeChanges($before, $after));

        $this->form->fill(TestConfig::current());
        $this->resetTable();

        Notification::make()->success()
            ->title($version ? 'Đã lưu cài đặt (phiên bản '.$version->id.')' : 'Không có thay đổi nào')
            ->body($version ? 'Lượt làm bài mới dùng ngay cài đặt này; kết quả cũ giữ nguyên.' : null)
            ->send();
    }

    public function table(Table $table): Table
    {
        $currentId = TestConfig::currentVersion()?->id;

        return $table
            ->query(TestConfigVersion::query()->with('user'))
            ->defaultSort('id', 'desc')
            ->paginated([10, 25])
            ->columns([
                TextColumn::make('id')->label('Bản')->badge()
                    ->formatStateUsing(fn (int $state) => $state === $currentId ? "Bản {$state} · đang dùng" : "Bản {$state}")
                    ->color(fn (int $state) => $state === $currentId ? 'success' : 'gray'),
                TextColumn::make('note')->label('Đã đổi gì')->wrap(),
                TextColumn::make('user.name')->label('Ai đổi')->default('Hệ thống'),
                TextColumn::make('created_at')->label('Lúc')->dateTime('H:i d/m/Y'),
            ])
            ->recordActions([
                Action::make('restore')
                    ->label('Quay lại bản này')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->requiresConfirmation()
                    ->modalHeading(fn (TestConfigVersion $record) => "Quay lại cài đặt của phiên bản {$record->id}?")
                    ->hidden(fn (TestConfigVersion $record) => $record->id === TestConfig::currentVersion()?->id)
                    ->authorize(fn () => static::canAccess())
                    ->action(function (TestConfigVersion $record): void {
                        $saved = TestConfig::save($record->settings, auth()->user(), 'Quay lại phiên bản '.$record->id.' ('.$record->created_at->format('H:i d/m/Y').')');
                        $this->form->fill(TestConfig::current());

                        Notification::make()->success()
                            ->title($saved ? "Đã quay lại phiên bản {$record->id} (lưu thành phiên bản {$saved->id})" : 'Cài đặt hiện tại đã giống phiên bản này')
                            ->send();
                    }),
            ]);
    }

    /**
     * Một ô trên form theo kiểu khai báo trong TestConfig::FIELDS.
     *
     * @param  array{0: string, 1: string, 2: string, 3?: string}  $field
     */
    #[Unexposed]
    protected function field(string $key, array $field): Component
    {
        [$label, $help, $type] = $field;
        $unit = $field[3] ?? null;

        return match ($type) {
            'int' => TextInput::make($key)->label($label)->helperText($help)->integer()->required()->suffix($unit),
            'decimal' => TextInput::make($key)->label($label)->helperText($help)->numeric()->step('any')->required()->suffix($unit),
            'bool' => Toggle::make($key)->label($label)->helperText($help)->columnSpanFull(),
            'labels5' => Fieldset::make($label)->columns(5)->columnSpanFull()->components(
                collect(range(0, 4))->map(fn (int $i) => TextInput::make("{$key}.{$i}")
                    ->label(($i + 1).' sao')->required()->maxLength(60)
                    ->helperText($i === 0 ? $help : null))->all(),
            ),
            default => $this->labelTable($key, $label, $help, $type),
        };
    }

    /**
     * Bảng mốc → nhãn (nhãn mức độ quan tâm, nhãn chỉ số C, nhãn nền tảng học tập).
     */
    #[Unexposed]
    protected function labelTable(string $key, string $label, string $help, string $type): Repeater
    {
        [$column, $columnLabel] = match ($type) {
            'bands' => ['max', 'Điểm trung bình đến (≤)'],
            'c_labels' => ['min', 'Chỉ số C từ (≥)'],
            default => ['min', 'Chỉ số từ (≥, thang 10)'],
        };

        return Repeater::make($key)->label($label)->helperText($help)
            ->columnSpanFull()->columns(2)
            ->reorderable(false)->minItems(1)->maxItems(7)
            ->addActionLabel('Thêm mốc')
            ->components([
                TextInput::make($column)->label($columnLabel)->numeric()->step('any')->required(),
                TextInput::make('label')->label('Nhãn')->required()->maxLength(60),
            ]);
    }
}
