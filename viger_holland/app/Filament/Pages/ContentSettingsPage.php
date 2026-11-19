<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Filament\Support\ContentFields;
use App\Models\AdminActivity;
use App\Models\Setting;
use App\Support\AdminMenu;
use App\Support\ContentPages;
use App\Support\ContentPreview;
use App\Support\HistoryReverter;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Làm gì:   Nền chung của các trang nội dung khách xem (config/admin_content.php): form theo từng phần, nút "Lưu",
 *           "Xem trước" (mở trang khách ở thẻ mới, chưa lưu), bảng "Lịch sử thay đổi" có "Quay lại bản này".
 *           Thêm ô mới: chỉ thêm một dòng trong config/admin_content.php.
 * Ai gọi:   Các trang con: ReportTexts, EmbedBox, Appearance, SeoSettings, IntroPage (khai báo $menuKey, $contentKey).
 * Dữ liệu:  settings (giá trị), admin_activities (lịch sử, subject settings.<trang>).
 */
abstract class ContentSettingsPage extends Page implements HasTable
{
    use InteractsWithTable;
    use UsesAdminMenu;

    /** Khóa trang trong config/admin_content.php. */
    protected static string $contentKey;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return AdminMenu::allows(auth()->user(), static::$menuKey);
    }

    /**
     * @return array<string, mixed>
     */
    public static function contentConfig(): array
    {
        return ContentPages::find(static::$contentKey) ?? [];
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
        $this->fillForm();
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];
        foreach (static::contentConfig()['sections'] as $title => $fields) {
            $sections[] = Section::make($title)
                ->collapsible()
                ->columns(2)
                ->components(array_map(fn (array $field) => ContentFields::make($field), $fields));
        }

        return $schema->components($sections)->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->settingsForm(),
            ...$this->extraContent(),
            $this->historySection(),
        ]);
    }

    #[Unexposed]
    protected function settingsForm(): Form
    {
        $buttons = [Action::make('save')->label('Lưu')->submit('save')->keyBindings(['mod+s'])];
        if ($this->hasPreview()) {
            $buttons[] = Action::make('preview')->label('Xem trước')->color('gray')
                ->icon(Heroicon::OutlinedEye)
                ->tooltip('Mở trang khách ở thẻ mới với nội dung đang gõ, chưa lưu')
                ->action(fn () => $this->preview());
        }

        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([Actions::make($buttons)->sticky()]);
    }

    /**
     * Phần thêm giữa form và lịch sử (ví dụ đoạn mã cho người làm web chính).
     *
     * @return list<Component>
     */
    #[Unexposed]
    protected function extraContent(): array
    {
        return [];
    }

    #[Unexposed]
    protected function historySection(): Section
    {
        return Section::make('Lịch sử thay đổi')
            ->description('Sửa nhầm thì bấm "Quay lại bản này" ở lần sửa đó. Quay lại cũng được ghi thành một dòng mới.')
            ->collapsible()
            ->components([EmbeddedTable::make()]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 404);

        $config = static::contentConfig();
        $values = $this->validated($config, $this->form->getState(), 'data');
        $current = ContentPages::currentValues($config);

        $before = [];
        $after = [];
        foreach ($values as $key => $value) {
            if ((string) ($current[$key] ?? '') !== $value) {
                $before[$key] = $current[$key] ?? null;
                $after[$key] = $value;
                Setting::put($key, $value, $config['group']);
            }
        }

        if ($after === []) {
            Notification::make()->title('Không có thay đổi nào')->send();

            return;
        }

        Cache::forget('holland.public_intro');
        AdminActivity::record(AdminActivity::ACTION_UPDATE, $this->historySubjects()[0], null,
            'Sửa "'.$config['title'].'" ('.count($after).' ô)', $before, $after);

        $this->fillForm();
        $this->resetTable();
        Notification::make()->success()->title('Đã lưu')->body('Trang khách hiện ngay nội dung mới.')->send();
    }

    public function preview(): void
    {
        abort_unless(static::canAccess() && $this->hasPreview(), 404);

        $values = $this->validated(static::contentConfig(), $this->form->getState(), 'data');
        $this->openInNewTab(ContentPreview::put(static::contentConfig()['preview'], $values));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AdminActivity::query()->with('user')->whereIn('subject_type', $this->historySubjects()))
            ->defaultSort('id', 'desc')
            ->paginated([10, 25])
            ->emptyStateHeading('Chưa có thay đổi nào được ghi lại')
            ->columns([
                TextColumn::make('created_at')->label('Lúc')->dateTime('H:i d/m/Y'),
                TextColumn::make('user.name')->label('Ai sửa')->default('Hệ thống'),
                TextColumn::make('description')->label('Đã sửa gì')->wrap(),
            ])
            ->recordActions([
                Action::make('revert')
                    ->label('Quay lại bản này')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->requiresConfirmation()
                    ->modalHeading('Quay lại nội dung trước lần sửa này?')
                    ->visible(fn (AdminActivity $record) => HistoryReverter::allows(auth()->user(), $record))
                    ->authorize(fn (AdminActivity $record) => HistoryReverter::allows(auth()->user(), $record))
                    ->action(function (AdminActivity $record): void {
                        $changed = HistoryReverter::revert($record);
                        $this->fillForm();
                        Notification::make()->success()
                            ->title($changed ? 'Đã quay lại bản trước' : 'Nội dung hiện tại đã giống bản trước')->send();
                    }),
            ]);
    }

    /**
     * Loại lịch sử hiện ở trang này; phần tử đầu là loại ghi khi bấm "Lưu".
     *
     * @return list<string>
     */
    #[Unexposed]
    protected function historySubjects(): array
    {
        return [AdminActivity::SUBJECT_SETTINGS.'.'.static::$contentKey];
    }

    #[Unexposed]
    protected function hasPreview(): bool
    {
        return filled(static::contentConfig()['preview'] ?? null);
    }

    #[Unexposed]
    protected function fillForm(): void
    {
        $config = static::contentConfig();
        $values = ContentPages::currentValues($config);
        foreach (ContentPages::fields($config) as $field) {
            if ($field['type'] === 'links') {
                $values[$field['key']] = ContentPages::links($values[$field['key']]);
            }
        }

        $this->form->fill($values);
    }

    /**
     * Kiểm tra bằng ContentPages (lời báo tiếng Việt), gắn lỗi vào đúng ô trên form.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $state
     * @return array<string, string>
     */
    #[Unexposed]
    protected function validated(array $config, array $state, string $statePath): array
    {
        try {
            return ContentPages::validate($config, $state);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())
                ->mapWithKeys(fn (array $messages, string $key) => ["{$statePath}.".explode('.', $key)[0] => $messages])
                ->all());
        }
    }

    #[Unexposed]
    protected function openInNewTab(string $url): void
    {
        $this->js('window.open('.json_encode($url).', "_blank")');
    }
}
