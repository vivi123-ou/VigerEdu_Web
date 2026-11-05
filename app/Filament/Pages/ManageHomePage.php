<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\HomePage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
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

class ManageHomePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Trang chủ';

    protected static ?string $title = 'Quản trị giao diện Trang chủ';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung Website';

    protected static ?string $slug = 'trang-chu';

    protected static ?int $navigationSort = 0;

    /** Ảnh upload: điền giá trị thô (không điền ảnh mặc định vào FileUpload). */
    private const IMAGE_KEYS = [
        'home_hero_background',
        'home_hero_image',
        'home_roadmap_image',
        'home_about_image_1',
        'home_about_image_2',
        'home_about_image_3',
    ];

    private const SECTION_LABELS = [
        'home_hero_' => 'Trang chủ › Banner',
        'home_roadmap_' => 'Trang chủ › Lộ trình',
        'home_partners' => 'Trang chủ › Đối tác',
        'home_about_' => 'Trang chủ › Về chúng tôi',
        'home_process_' => 'Trang chủ › Quy trình',
        'home_testimonial' => 'Trang chủ › Cảm nhận',
        'home_cta_' => 'Trang chủ › CTA',
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $data = HomePage::all();

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
                Tabs::make('home')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Banner đầu trang')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->schema($this->heroSchema()),
                        Tab::make('Đối tác')
                            ->icon(Heroicon::OutlinedBuildingLibrary)
                            ->schema($this->partnersSchema()),
                        Tab::make('Về chúng tôi')
                            ->icon(Heroicon::OutlinedUserGroup)
                            ->schema($this->aboutSchema()),
                        Tab::make('Quy trình')
                            ->icon(Heroicon::OutlinedListBullet)
                            ->schema($this->processSchema()),
                        Tab::make('Cảm nhận học viên')
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->schema($this->testimonialsSchema()),
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
            ->directory('home');
    }

    private function heroSchema(): array
    {
        return [
            Section::make('Nội dung')
                ->schema([
                    TextInput::make('home_hero_heading')->label('Tiêu đề (dòng trắng)')->maxLength(255),
                    TextInput::make('home_hero_subheading')->label('Tiêu đề phụ (dòng vàng)')->maxLength(255),
                    Textarea::make('home_hero_description')->label('Mô tả')->rows(3)->columnSpanFull(),
                ])->columns(2),

            Section::make('Số liệu nổi bật')
                ->description('Các con số chạy hiệu ứng đếm trong banner.')
                ->schema([
                    Repeater::make('home_hero_stats')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('number')->label('Con số')->numeric()->required(),
                            TextInput::make('suffix')->label('Hậu tố')->placeholder('+ hoặc %')->maxLength(5),
                            TextInput::make('label')->label('Mô tả')->required()->maxLength(100),
                        ])
                        ->columns(3)
                        ->reorderable()
                        ->maxItems(4)
                        ->addActionLabel('Thêm số liệu')
                        ->itemLabel(fn (array $state): ?string => trim(($state['number'] ?? '').($state['suffix'] ?? '').' '.($state['label'] ?? '')) ?: null),
                ]),

            Section::make('Nút bấm')
                ->schema([
                    TextInput::make('home_hero_button_text')->label('Nút chính: chữ')->maxLength(100),
                    TextInput::make('home_hero_button_url')->label('Nút chính: liên kết')->maxLength(255),
                    TextInput::make('home_roadmap_button_text')
                        ->label('Nút phụ: chữ')
                        ->helperText('Nút phụ mở popup hiển thị ảnh lộ trình du học.')
                        ->maxLength(100),
                    $this->imageUpload('home_roadmap_image', 'Ảnh lộ trình (popup)'),
                ])->columns(2),

            Section::make('Hình ảnh')
                ->schema([
                    $this->imageUpload('home_hero_background', 'Ảnh nền banner'),
                    $this->imageUpload('home_hero_image', 'Ảnh minh họa bên phải'),
                ])->columns(2),
        ];
    }

    private function partnersSchema(): array
    {
        return [
            Section::make('Dải trường đối tác (chữ chạy trên nền vàng)')
                ->schema([
                    Repeater::make('home_partners')
                        ->hiddenLabel()
                        ->simple(TextInput::make('name')->label('Tên trường')->required()->maxLength(255))
                        ->reorderable()
                        ->addActionLabel('Thêm trường'),
                ]),
        ];
    }

    private function aboutSchema(): array
    {
        return [
            Section::make('Nội dung')
                ->schema([
                    TextInput::make('home_about_label')->label('Nhãn nhỏ')->maxLength(100),
                    TextInput::make('home_about_heading')->label('Tiêu đề')->maxLength(255),
                    Textarea::make('home_about_description')->label('Mô tả')->rows(3)->columnSpanFull(),
                    TextInput::make('home_about_link_text')->label('Liên kết: chữ')->maxLength(100),
                    TextInput::make('home_about_link_url')->label('Liên kết: đường dẫn')->maxLength(255),
                ])->columns(2),

            Section::make('Ô số liệu')
                ->schema([
                    TextInput::make('home_about_stat_number')->label('Con số')->maxLength(20),
                    TextInput::make('home_about_stat_label')->label('Mô tả')->maxLength(100),
                ])->columns(2),

            Section::make('Hình ảnh (lưới 3 ảnh)')
                ->schema([
                    $this->imageUpload('home_about_image_1', 'Ảnh 1 (trên trái)'),
                    $this->imageUpload('home_about_image_2', 'Ảnh 2 (trên phải)'),
                    $this->imageUpload('home_about_image_3', 'Ảnh 3 (dưới)'),
                ])->columns(3),
        ];
    }

    private function processSchema(): array
    {
        return [
            Section::make('Tiêu đề')
                ->schema([
                    TextInput::make('home_process_pretitle')->label('Nhãn nhỏ')->maxLength(100),
                    TextInput::make('home_process_title')->label('Tiêu đề')->maxLength(255),
                ])->columns(2),

            Section::make('Các bước')
                ->description('Số thứ tự (01, 02, ...) được đánh tự động theo thứ tự sắp xếp.')
                ->schema([
                    Repeater::make('home_process_steps')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('title')->label('Tên bước')->required()->maxLength(100),
                            TextInput::make('description')->label('Mô tả ngắn')->maxLength(255),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->collapsible()
                        ->addActionLabel('Thêm bước')
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                ]),

            Section::make('Liên kết bên dưới')
                ->schema([
                    TextInput::make('home_process_link_text')->label('Chữ')->maxLength(100),
                    TextInput::make('home_process_link_url')->label('Đường dẫn')->maxLength(255),
                ])->columns(2),
        ];
    }

    private function testimonialsSchema(): array
    {
        return [
            Section::make('Cảm nhận học viên')
                ->schema([
                    TextInput::make('home_testimonial_title')->label('Tiêu đề khối')->maxLength(255),
                    Repeater::make('home_testimonials')
                        ->label('Danh sách cảm nhận')
                        ->schema([
                            Textarea::make('content')->label('Nội dung')->rows(3)->required()->columnSpanFull(),
                            TextInput::make('name')->label('Tên học viên')->required()->maxLength(100),
                            FileUpload::make('avatar')
                                ->label('Ảnh đại diện')
                                ->helperText('Bỏ trống: dùng ảnh hiện tại (nếu có) hoặc chữ cái đầu của tên.')
                                ->image()
                                ->avatar()
                                ->disk('public')
                                ->directory('home/testimonials'),
                            // Ảnh có sẵn của dữ liệu mặc định (public/img/...)
                            Hidden::make('default_avatar'),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->collapsible()
                        ->addActionLabel('Thêm cảm nhận')
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                ]),
        ];
    }

    private function ctaSchema(): array
    {
        return [
            Section::make('Khối kêu gọi hành động cuối trang')
                ->schema([
                    TextInput::make('home_cta_pretitle')->label('Nhãn nhỏ')->maxLength(100),
                    TextInput::make('home_cta_title')->label('Tiêu đề')->maxLength(255),
                    Textarea::make('home_cta_description')->label('Mô tả')->rows(2)->columnSpanFull(),
                    TextInput::make('home_cta_button_text')->label('Nút: chữ')->maxLength(100),
                    TextInput::make('home_cta_button_url')->label('Nút: liên kết')->maxLength(255),
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
                            ->label('Xem Trang chủ')
                            ->color('gray')
                            ->url(url('/'), shouldOpenInNewTab: true),
                    ])->sticky(),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (array_keys(HomePage::DEFAULTS) as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if (in_array($key, HomePage::JSON_KEYS, true)) {
                // Bỏ key UUID của Repeater; danh sách rỗng => null (dùng mặc định)
                $value = is_array($value) && count($value) ? array_values($value) : null;
            }

            Setting::set(
                $key,
                $value,
                'home',
                match (true) {
                    in_array($key, self::IMAGE_KEYS, true) => 'image',
                    in_array($key, HomePage::JSON_KEYS, true) => 'json',
                    default => 'text',
                },
                $this->labelFor($key),
            );
        }

        Notification::make()->success()->title('Đã lưu nội dung Trang chủ.')->send();
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
