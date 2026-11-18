<?php

namespace App\Filament\Pages;

use App\Filament\Forms\Components\TranslatableTabs;
use App\Models\Setting;
use App\Support\CategoryHeroPages;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Quản trị banner đầu trang (hero) của các trang con trong một menu,
 * bản Tiếng Việt và bản English (?lang=en).
 *
 * Lớp con khai báo $content (lớp CategoryHeroPages), $menuName, $menuUrl cùng các thuộc tính điều hướng.
 */
abstract class ManageCategoryHeroPages extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Nội dung Website';

    /** @var class-string<CategoryHeroPages> */
    protected static string $content;

    /** Tên menu hiển thị trong admin, vd. "Giáo dục tại Đức". */
    protected static string $menuName;

    /** Trang chính của menu (nút "Xem trang"). */
    protected static string $menuUrl;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** Ngôn ngữ đang sửa (?lang=en): ngôn ngữ phụ chỉ sửa phần chữ, ảnh dùng chung. */
    #[Url(as: 'lang')]
    public string $editLocale = 'vi';

    public function mount(): void
    {
        if (! in_array($this->editLocale, config('app.supported_locales'), true)) {
            $this->editLocale = static::$content::primaryLocale();
        }

        $data = static::$content::all($this->editLocale);

        // Ảnh upload: điền giá trị thô (không điền ảnh mặc định vào FileUpload)
        foreach (array_keys(static::$content::PAGES) as $page) {
            $key = static::$content::key($page, 'image');
            $data[$key] = Setting::get($key);
        }

        $this->form->fill($data);
    }

    /** Đang sửa bản dịch (ngôn ngữ phụ)? */
    public function isTranslating(): bool
    {
        return $this->editLocale !== static::$content::primaryLocale();
    }

    public function getSubheading(): ?string
    {
        $language = TranslatableTabs::LOCALE_LABELS[$this->editLocale] ?? strtoupper($this->editLocale);

        return $this->isTranslating()
            ? "Đang sửa bản {$language}. Chỉ sửa được phần chữ; ảnh nền dùng chung với bản Tiếng Việt. Ô để trống sẽ dùng bản dịch mặc định."
            : "Đang sửa bản {$language}. Danh sách bài viết của từng trang được quản trị ở mục Bài viết.";
    }

    protected function getHeaderActions(): array
    {
        return collect(config('app.supported_locales'))
            ->map(fn (string $locale) => Action::make("edit_{$locale}")
                ->label('Sửa bản '.(TranslatableTabs::LOCALE_LABELS[$locale] ?? strtoupper($locale)))
                ->icon(Heroicon::OutlinedLanguage)
                ->color($locale === $this->editLocale ? 'primary' : 'gray')
                ->url(static::getUrl(['lang' => $locale])))
            ->all();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('pages')
                    ->persistTabInQueryString()
                    ->tabs(collect(static::$content::PAGES)
                        ->map(fn (array $page, string $id) => Tab::make($page['label'])
                            ->key("page-{$id}")
                            ->schema($this->pageSchema($id, $page)))
                        ->values()
                        ->all()),
            ]);
    }

    /**
     * @param  array{label: string, url: string, image: string}  $page
     */
    private function pageSchema(string $id, array $page): array
    {
        return [
            Section::make('Banner đầu trang')
                ->description(new HtmlString('Trang: <a href="'.e(url($page['url'])).'" target="_blank" class="underline">'.e($page['url']).'</a>'))
                ->schema([
                    TextInput::make(static::$content::key($id, 'page_title'))
                        ->label('Tiêu đề tab trình duyệt')
                        ->maxLength(255),
                    TextInput::make(static::$content::key($id, 'pretitle'))
                        ->label('Dòng nhỏ phía trên tiêu đề')
                        ->maxLength(255),
                    TextInput::make(static::$content::key($id, 'heading'))
                        ->label('Tiêu đề lớn')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make(static::$content::key($id, 'description'))
                        ->label('Mô tả')
                        ->rows(3)
                        ->columnSpanFull(),
                    FileUpload::make(static::$content::key($id, 'image'))
                        ->label('Ảnh nền banner')
                        ->helperText('Bỏ trống để dùng ảnh mặc định. Ảnh dùng chung cho mọi ngôn ngữ.')
                        ->image()
                        ->disk('public')
                        ->directory(static::$content::PREFIX)
                        ->columnSpanFull()
                        ->disabled(fn (): bool => $this->isTranslating()),
                ])->columns(2),
            ...$this->extraSchema($id),
        ];
    }

    /**
     * Các khối thêm của một trang con (vd. khối kêu gọi cuối trang). Key phải có trong DEFAULTS của $content;
     * key có trong TRANSLATIONS được sửa ở cả bản dịch, còn lại (liên kết...) nên khóa bằng shared().
     *
     * @return array<Component>
     */
    protected function extraSchema(string $id): array
    {
        return [];
    }

    /** Trường dùng chung cho mọi ngôn ngữ (liên kết...): khóa khi sửa bản dịch. */
    protected function shared(Field $field): Field
    {
        return $field->disabled(fn (): bool => $this->isTranslating());
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Lưu thay đổi')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                        Action::make('view')
                            ->label('Xem trang '.static::$menuName)
                            ->color('gray')
                            ->url(url(static::$menuUrl), shouldOpenInNewTab: true),
                    ])->sticky(),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Bản dịch lưu ở setting "{key}_{locale}", chỉ các trường chữ
        $keys = $this->isTranslating()
            ? array_keys(static::$content::TRANSLATIONS[$this->editLocale] ?? [])
            : array_keys(static::$content::DEFAULTS);

        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $isImage = str_ends_with($key, '_image');

            Setting::set(
                $this->isTranslating() ? static::$content::translationKey($key, $this->editLocale) : $key,
                $data[$key],
                static::$content::PREFIX,
                $isImage ? 'image' : 'text',
                $this->labelFor($key),
            );
        }

        $language = TranslatableTabs::LOCALE_LABELS[$this->editLocale] ?? $this->editLocale;

        Notification::make()->success()->title("Đã lưu bản {$language}: ".static::$menuName.'.')->send();
    }

    private function labelFor(string $key): string
    {
        $label = static::$menuName.' ('.$key.')';

        foreach (static::$content::PAGES as $id => $page) {
            if (str_starts_with($key, static::$content::key($id, ''))) {
                $label = static::$menuName.' › '.$page['label'].' ('.$key.')';

                break;
            }
        }

        return $this->isTranslating() ? $label.' ['.strtoupper($this->editLocale).']' : $label;
    }
}
