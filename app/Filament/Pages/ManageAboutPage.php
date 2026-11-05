<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\AboutPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageAboutPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Trang Về chúng tôi';

    protected static ?string $title = 'Quản trị giao diện trang Về chúng tôi';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung Website';

    protected static ?string $slug = 'trang-ve-chung-toi';

    protected static ?int $navigationSort = 10;

    /** Ảnh upload: điền giá trị thô (không điền ảnh mặc định vào FileUpload). */
    private const IMAGE_KEYS = ['about_hero_image', 'about_intro_image', 'about_history_image'];

    private const SECTION_LABELS = [
        'about_hero_' => 'Về chúng tôi › Banner',
        'about_intro_' => 'Về chúng tôi › Giới thiệu',
        'about_values_' => 'Về chúng tôi › Giá trị',
        'about_history_' => 'Về chúng tôi › Hành trình',
        'about_cta_' => 'Về chúng tôi › CTA',
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $data = AboutPage::all();

        foreach (self::IMAGE_KEYS as $key) {
            $data[$key] = Setting::get($key);
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('about')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Banner đầu trang')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->schema($this->heroSchema()),
                        Tab::make('Giới thiệu lộ trình')
                            ->icon(Heroicon::OutlinedMap)
                            ->schema($this->introSchema()),
                        Tab::make('Giá trị cốt lõi')
                            ->icon(Heroicon::OutlinedListBullet)
                            ->schema($this->valuesSchema()),
                        Tab::make('Hành trình phát triển')
                            ->icon(Heroicon::OutlinedClock)
                            ->schema($this->historySchema()),
                        Tab::make('Kêu gọi cuối trang')
                            ->icon(Heroicon::OutlinedMegaphone)
                            ->schema($this->ctaSchema()),
                    ]),
            ]);
    }

    private function imageUpload(string $key, string $label): FileUpload
    {
        return FileUpload::make($key)
            ->label($label)
            ->helperText('Bỏ trống để dùng ảnh mặc định.')
            ->image()
            ->disk('public')
            ->directory('about');
    }

    private function heroSchema(): array
    {
        return [
            Section::make('Banner đầu trang')
                ->schema([
                    TextInput::make('about_hero_pretitle')->label('Dòng chữ nhỏ phía trên')->maxLength(255),
                    TextInput::make('about_hero_title')->label('Tiêu đề lớn')->required()->maxLength(255),
                    Textarea::make('about_hero_description')->label('Mô tả')->rows(2)->columnSpanFull(),
                    $this->imageUpload('about_hero_image', 'Ảnh nền banner')->columnSpanFull(),
                ])->columns(2),
        ];
    }

    private function introSchema(): array
    {
        return [
            Section::make('Nội dung')
                ->schema([
                    TextInput::make('about_intro_label')->label('Nhãn nhỏ')->maxLength(100),
                    TextInput::make('about_intro_heading')->label('Tiêu đề')->maxLength(255),
                    Textarea::make('about_intro_highlight')
                        ->label('Đoạn nổi bật (trong khung)')
                        ->rows(3)
                        ->helperText('Đặt chữ trong **hai dấu sao** để tô đậm màu nhấn.')
                        ->columnSpanFull(),
                    Textarea::make('about_intro_description')
                        ->label('Mô tả')
                        ->rows(5)
                        ->helperText('Cách một dòng trống để tách đoạn.')
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('Hình ảnh')
                ->schema([
                    $this->imageUpload('about_intro_image', 'Ảnh bên trái'),
                ]),
        ];
    }

    private function valuesSchema(): array
    {
        return [
            Section::make('Tiêu đề khối')
                ->schema([
                    Textarea::make('about_values_heading')
                        ->label('Tiêu đề')
                        ->rows(3)
                        ->helperText('Xuống dòng sẽ được giữ nguyên khi hiển thị.'),
                    Textarea::make('about_values_description')->label('Mô tả')->rows(3),
                ])->columns(2),

            Section::make('Danh sách mục (accordion)')
                ->schema([
                    Repeater::make('about_values_items')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255),
                            Textarea::make('body')->label('Nội dung')->rows(3),
                        ])
                        ->reorderable()
                        ->collapsible()
                        ->addActionLabel('Thêm mục')
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                ]),
        ];
    }

    private function historySchema(): array
    {
        return [
            Section::make('Nội dung')
                ->schema([
                    TextInput::make('about_history_label')->label('Nhãn nhỏ')->maxLength(100),
                    TextInput::make('about_history_heading')->label('Tiêu đề')->maxLength(255),
                    Textarea::make('about_history_description')
                        ->label('Mô tả')
                        ->rows(6)
                        ->helperText('Cách một dòng trống để tách đoạn.')
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('Hình ảnh')
                ->schema([
                    $this->imageUpload('about_history_image', 'Ảnh bên phải'),
                ]),
        ];
    }

    private function ctaSchema(): array
    {
        return [
            Section::make('Khối kêu gọi hành động cuối trang')
                ->schema([
                    TextInput::make('about_cta_pretitle')->label('Nhãn nhỏ')->maxLength(100),
                    TextInput::make('about_cta_title')->label('Tiêu đề')->maxLength(255),
                    Textarea::make('about_cta_description')->label('Mô tả')->rows(2)->columnSpanFull(),
                    TextInput::make('about_cta_button_text')->label('Nút: chữ')->maxLength(100),
                    TextInput::make('about_cta_button_url')->label('Nút: liên kết')->maxLength(255),
                ])->columns(2),
        ];
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
                            ->label('Xem trang Về chúng tôi')
                            ->color('gray')
                            ->url(url('/ve-chung-toi'), shouldOpenInNewTab: true),
                    ])->sticky(),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (array_keys(AboutPage::DEFAULTS) as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if (in_array($key, AboutPage::JSON_KEYS, true)) {
                // Bỏ key UUID của Repeater; danh sách rỗng => null (dùng mặc định)
                $value = is_array($value) && count($value) ? array_values($value) : null;
            }

            Setting::set(
                $key,
                $value,
                'about',
                match (true) {
                    in_array($key, self::IMAGE_KEYS, true) => 'image',
                    in_array($key, AboutPage::JSON_KEYS, true) => 'json',
                    default => 'text',
                },
                $this->labelFor($key),
            );
        }

        Notification::make()->success()->title('Đã lưu nội dung trang Về chúng tôi.')->send();
    }

    private function labelFor(string $key): string
    {
        foreach (self::SECTION_LABELS as $prefix => $label) {
            if (str_starts_with($key, $prefix)) {
                return $label.' ('.$key.')';
            }
        }

        return $key;
    }
}
