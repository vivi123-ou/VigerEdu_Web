<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\ConsultProcessPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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

class ManageConsultProcessPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $navigationLabel = 'Trang Quy trình tư vấn';

    protected static ?string $title = 'Quản trị giao diện trang Quy trình tư vấn';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung Website';

    protected static ?string $slug = 'trang-quy-trinh-tu-van';

    protected static ?int $navigationSort = 30;

    /** Ảnh upload: điền giá trị thô (không điền ảnh mặc định vào FileUpload). */
    private const IMAGE_KEYS = ['consult_process_hero_image'];

    private const SECTION_LABELS = [
        'consult_process_hero_' => 'Quy trình tư vấn › Banner',
        'consult_process_steps' => 'Quy trình tư vấn › Các bước',
        'consult_process_services' => 'Quy trình tư vấn › Dịch vụ',
        'consult_process_cta_' => 'Quy trình tư vấn › CTA',
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $data = ConsultProcessPage::all();

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
                Tabs::make('consult_process')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Banner đầu trang')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->schema($this->heroSchema()),
                        Tab::make('Các bước quy trình')
                            ->icon(Heroicon::OutlinedListBullet)
                            ->schema($this->stepsSchema()),
                        Tab::make('Dịch vụ tư vấn')
                            ->icon(Heroicon::OutlinedSquares2x2)
                            ->schema($this->servicesSchema()),
                        Tab::make('Kêu gọi cuối trang')
                            ->icon(Heroicon::OutlinedMegaphone)
                            ->schema($this->ctaSchema()),
                    ]),
            ]);
    }

    private function heroSchema(): array
    {
        return [
            Section::make('Banner đầu trang')
                ->schema([
                    TextInput::make('consult_process_hero_pretitle')->label('Dòng chữ nhỏ phía trên')->maxLength(255),
                    TextInput::make('consult_process_hero_title')->label('Tiêu đề lớn')->required()->maxLength(255),
                    Textarea::make('consult_process_hero_description')->label('Mô tả')->rows(3)->columnSpanFull(),
                    FileUpload::make('consult_process_hero_image')
                        ->label('Ảnh nền banner')
                        ->helperText('Bỏ trống để dùng ảnh mặc định.')
                        ->image()
                        ->disk('public')
                        ->directory('consult')
                        ->columnSpanFull(),
                ])->columns(2),
        ];
    }

    private function stepsSchema(): array
    {
        return [
            Section::make('Tiêu đề')
                ->schema([
                    TextInput::make('consult_process_steps_pretitle')->label('Nhãn nhỏ')->maxLength(100),
                    TextInput::make('consult_process_steps_title')
                        ->label('Tiêu đề')
                        ->helperText('Nhớ cập nhật số bước trong tiêu đề nếu thêm/bớt bước.')
                        ->maxLength(255),
                ])->columns(2),

            Section::make('Các bước')
                ->description('Số thứ tự (01, 02, ...) được đánh tự động theo thứ tự sắp xếp.')
                ->schema([
                    Repeater::make('consult_process_steps')
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
        ];
    }

    private function servicesSchema(): array
    {
        return [
            Section::make('Tiêu đề')
                ->schema([
                    TextInput::make('consult_process_services_pretitle')->label('Nhãn nhỏ')->maxLength(100),
                    TextInput::make('consult_process_services_title')->label('Tiêu đề')->maxLength(255),
                    Textarea::make('consult_process_services_description')->label('Mô tả')->rows(2)->columnSpanFull(),
                ])->columns(2),

            Section::make('Thẻ dịch vụ')
                ->schema([
                    Repeater::make('consult_process_services')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('title')->label('Tên dịch vụ')->required()->maxLength(100),
                            Select::make('icon')
                                ->label('Biểu tượng')
                                ->options(ConsultProcessPage::iconOptions())
                                ->default('check')
                                ->required(),
                            Textarea::make('body')->label('Mô tả')->rows(3)->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->collapsible()
                        ->addActionLabel('Thêm dịch vụ')
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                ]),
        ];
    }

    private function ctaSchema(): array
    {
        return [
            Section::make('Khối kêu gọi hành động cuối trang')
                ->schema([
                    TextInput::make('consult_process_cta_pretitle')->label('Nhãn nhỏ')->maxLength(100),
                    TextInput::make('consult_process_cta_title')->label('Tiêu đề')->maxLength(255),
                    Textarea::make('consult_process_cta_description')->label('Mô tả')->rows(2)->columnSpanFull(),
                    TextInput::make('consult_process_cta_button_text')->label('Nút: chữ')->maxLength(100),
                    TextInput::make('consult_process_cta_button_url')->label('Nút: liên kết')->maxLength(255),
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
                            ->label('Xem trang Quy trình tư vấn')
                            ->color('gray')
                            ->url(url('/tu-van/quy-trinh-tu-van'), shouldOpenInNewTab: true),
                    ])->sticky(),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (array_keys(ConsultProcessPage::DEFAULTS) as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if (in_array($key, ConsultProcessPage::JSON_KEYS, true)) {
                // Bỏ key UUID của Repeater; danh sách rỗng => null (dùng mặc định)
                $value = is_array($value) && count($value) ? array_values($value) : null;
            }

            Setting::set(
                $key,
                $value,
                'consult_process',
                match (true) {
                    in_array($key, self::IMAGE_KEYS, true) => 'image',
                    in_array($key, ConsultProcessPage::JSON_KEYS, true) => 'json',
                    default => 'text',
                },
                $this->labelFor($key),
            );
        }

        Notification::make()->success()->title('Đã lưu nội dung trang Quy trình tư vấn.')->send();
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
