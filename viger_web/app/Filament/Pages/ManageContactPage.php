<?php

namespace App\Filament\Pages;

use App\Filament\Forms\Components\TranslatableTabs;
use App\Models\Setting;
use App\Support\ContactPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use UnitEnum;

class ManageContactPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Trang Liên hệ';

    protected static ?string $title = 'Quản trị giao diện trang Liên hệ';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung Website';

    protected static ?string $slug = 'trang-lien-he';

    protected static ?int $navigationSort = 50;

    /** Ảnh upload: lưu giá trị thô (không điền ảnh mặc định vào FileUpload). */
    private const IMAGE_KEYS = ['contact_hero_image', 'contact_side_image'];

    /** Group của từng key trong bảng settings (key dùng chung giữ nguyên group cũ). */
    private const GROUPS = [
        'contact_email' => 'footer',
        'social_facebook' => 'social',
        'social_tiktok' => 'social',
        'social_youtube' => 'social',
    ];

    private const LABELS = [
        'contact_hero_pretitle' => 'Liên hệ: Dòng nhỏ trên banner',
        'contact_hero_title' => 'Liên hệ: Tiêu đề banner',
        'contact_hero_description' => 'Liên hệ: Mô tả banner',
        'contact_hero_image' => 'Liên hệ: Ảnh nền banner',
        'contact_heading' => 'Liên hệ: Tiêu đề khối thông tin',
        'contact_heading_highlight' => 'Liên hệ: Tiêu đề khối thông tin (phần tô màu)',
        'contact_phone' => 'Số điện thoại liên hệ',
        'contact_email' => 'Email liên hệ',
        'contact_address' => 'Địa chỉ liên hệ',
        'contact_side_image' => 'Liên hệ: Ảnh minh họa',
        'social_facebook' => 'Link Facebook',
        'social_tiktok' => 'Link TikTok',
        'social_youtube' => 'Link YouTube',
        'contact_form_pretitle' => 'Liên hệ: Dòng nhỏ trên form',
        'contact_form_title' => 'Liên hệ: Tiêu đề form',
        'contact_form_description' => 'Liên hệ: Mô tả form',
        'contact_form_button' => 'Liên hệ: Chữ trên nút gửi',
        'contact_success_message' => 'Liên hệ: Thông báo gửi thành công',
        'contact_show_map' => 'Liên hệ: Hiển thị bản đồ',
        'contact_map_url' => 'Liên hệ: Link nhúng Google Maps',
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** Ngôn ngữ đang sửa (?lang=en): bản dịch chỉ sửa phần chữ, ảnh/liên kết/số điện thoại dùng chung. */
    #[Url(as: 'lang')]
    public string $editLocale = 'vi';

    public function mount(): void
    {
        if (! in_array($this->editLocale, config('app.supported_locales'), true)) {
            $this->editLocale = ContactPage::primaryLocale();
        }

        $data = ContactPage::all($this->editLocale);

        foreach (self::IMAGE_KEYS as $key) {
            $data[$key] = Setting::get($key);
        }

        $data['contact_show_map'] = (bool) (int) $data['contact_show_map'];

        $this->form->fill($data);
    }

    public function isTranslating(): bool
    {
        return $this->editLocale !== ContactPage::primaryLocale();
    }

    public function getSubheading(): ?string
    {
        $language = TranslatableTabs::LOCALE_LABELS[$this->editLocale] ?? strtoupper($this->editLocale);

        return $this->isTranslating()
            ? "Đang sửa bản {$language}. Chỉ sửa được phần chữ; ảnh, số điện thoại, email, liên kết và bản đồ dùng chung với bản Tiếng Việt. Ô để trống sẽ dùng bản dịch mặc định."
            : "Đang sửa bản {$language}.";
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

    /** Trường dùng chung cho mọi ngôn ngữ: khóa khi sửa bản dịch. */
    private function shared(Field $field): Field
    {
        return $field->disabled(fn (): bool => $this->isTranslating());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Banner đầu trang')
                    ->schema([
                        TextInput::make('contact_hero_pretitle')->label('Dòng chữ nhỏ phía trên')->maxLength(255),
                        TextInput::make('contact_hero_title')->label('Tiêu đề lớn')->required()->maxLength(255),
                        Textarea::make('contact_hero_description')->label('Mô tả')->rows(2)->columnSpanFull(),
                        $this->shared(FileUpload::make('contact_hero_image')
                            ->label('Ảnh nền banner')
                            ->helperText('Bỏ trống để dùng ảnh mặc định.')
                            ->image()
                            ->disk('public')
                            ->directory('contact')
                            ->columnSpanFull()),
                    ])->columns(2),

                Section::make('Thông tin liên hệ')
                    ->schema([
                        TextInput::make('contact_heading')->label('Tiêu đề')->maxLength(255),
                        TextInput::make('contact_heading_highlight')->label('Tiêu đề (dòng tô màu)')->maxLength(255),
                        $this->shared(TextInput::make('contact_phone')->label('Điện thoại')->tel()->maxLength(50)),
                        $this->shared(TextInput::make('contact_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->helperText('Dùng chung với Footer.')),
                        Textarea::make('contact_address')
                            ->label('Địa chỉ')
                            ->rows(2)
                            ->helperText('Xuống dòng sẽ được giữ nguyên khi hiển thị.')
                            ->columnSpanFull(),
                        $this->shared(FileUpload::make('contact_side_image')
                            ->label('Ảnh minh họa bên phải')
                            ->helperText('Bỏ trống để dùng ảnh mặc định.')
                            ->image()
                            ->disk('public')
                            ->directory('contact')
                            ->columnSpanFull()),
                    ])->columns(2),

                Section::make('Mạng xã hội')
                    ->description('Dùng chung với Footer. Các ô văn bản để trống sẽ dùng nội dung mặc định.')
                    ->schema([
                        $this->shared(TextInput::make('social_facebook')->label('Facebook')->url()->maxLength(255)),
                        $this->shared(TextInput::make('social_tiktok')->label('TikTok')->url()->maxLength(255)),
                        $this->shared(TextInput::make('social_youtube')->label('YouTube')->url()->maxLength(255)),
                    ])->columns(3),

                Section::make('Form đăng ký tư vấn')
                    ->schema([
                        TextInput::make('contact_form_pretitle')->label('Dòng chữ nhỏ phía trên')->maxLength(255),
                        TextInput::make('contact_form_button')->label('Chữ trên nút gửi')->maxLength(100),
                        Textarea::make('contact_form_title')
                            ->label('Tiêu đề')
                            ->rows(2)
                            ->helperText('Đặt chữ trong **hai dấu sao** để tô màu vàng nhấn. Xuống dòng sẽ được giữ nguyên.')
                            ->columnSpanFull(),
                        Textarea::make('contact_form_description')->label('Mô tả')->rows(2)->columnSpanFull(),
                        TextInput::make('contact_success_message')
                            ->label('Thông báo sau khi gửi thành công')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Bản đồ')
                    ->schema([
                        $this->shared(Toggle::make('contact_show_map')->label('Hiển thị bản đồ')),
                        $this->shared(Textarea::make('contact_map_url')
                            ->label('Link nhúng Google Maps')
                            ->rows(3)
                            ->helperText('Trên Google Maps: Chia sẻ → Nhúng bản đồ → Sao chép HTML, rồi dán nguyên đoạn <iframe> hoặc chỉ link src (https://www.google.com/maps/embed?...) vào đây.')
                            ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                                if (filled($value) && ContactPage::mapSrc($value) === null) {
                                    $fail('Link bản đồ không hợp lệ (phải là link https hoặc đoạn <iframe> của Google Maps).');
                                }
                            })),
                    ]),
            ]);
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
                            ->label('Xem trang Liên hệ')
                            ->color('gray')
                            ->url(url('/lien-he'), shouldOpenInNewTab: true),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if ($this->isTranslating()) {
            $this->saveTranslation($data);

            return;
        }

        $data['contact_show_map'] = ! empty($data['contact_show_map']) ? '1' : '0';

        if (filled($data['contact_map_url'] ?? null)) {
            $data['contact_map_url'] = ContactPage::mapSrc($data['contact_map_url']);
        }

        foreach (array_keys(ContactPage::DEFAULTS) as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            Setting::set(
                $key,
                $data[$key],
                self::GROUPS[$key] ?? 'contact',
                in_array($key, self::IMAGE_KEYS, true) ? 'image' : 'text',
                self::LABELS[$key] ?? $key,
            );
        }

        Notification::make()->success()->title('Đã lưu nội dung trang Liên hệ.')->send();
    }

    /**
     * Lưu bản dịch vào setting "{key}_{locale}".
     *
     * @param  array<string, mixed>  $data
     */
    private function saveTranslation(array $data): void
    {
        foreach (array_keys(ContactPage::TRANSLATIONS[$this->editLocale] ?? []) as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            Setting::set(
                ContactPage::translationKey($key, $this->editLocale),
                $data[$key],
                'contact',
                'text',
                (self::LABELS[$key] ?? $key).' ['.strtoupper($this->editLocale).']',
            );
        }

        $language = TranslatableTabs::LOCALE_LABELS[$this->editLocale] ?? $this->editLocale;
        Notification::make()->success()->title("Đã lưu bản {$language} của trang Liên hệ.")->send();
    }
}
