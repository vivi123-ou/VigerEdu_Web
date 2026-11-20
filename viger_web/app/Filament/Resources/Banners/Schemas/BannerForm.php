<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Filament\Forms\Components\TranslatableTabs;
use App\Models\Banner;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nội dung Banner')
                    ->description('Tiêu đề, mô tả và nhãn nút hiển thị trên slider theo từng ngôn ngữ. Bản tiếng Anh để trống sẽ hiển thị bản tiếng Việt.')
                    ->schema([
                        TranslatableTabs::make(fn (string $locale, bool $isPrimary) => [
                            Grid::make(2)->schema([
                                TextInput::make("title.{$locale}")
                                    ->label('Tiêu đề chính')
                                    ->required($isPrimary)
                                    ->maxLength(255),
                                TextInput::make("eyebrow.{$locale}")
                                    ->label('Dòng phụ đề nhỏ (Eyebrow)')
                                    ->placeholder('Ví dụ: Du học Đức')
                                    ->maxLength(255),
                                Textarea::make("desc.{$locale}")
                                    ->label('Mô tả chi tiết')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                TextInput::make("alt.{$locale}")
                                    ->label('Mô tả hình ảnh (Alt text)')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make("button_primary_label.{$locale}")
                                    ->label('Nút chính (Tên nhãn)')
                                    ->placeholder('Ví dụ: Khám phá ngay')
                                    ->maxLength(255),
                                TextInput::make("button_secondary_label.{$locale}")
                                    ->label('Nút phụ (Tên nhãn)')
                                    ->placeholder('Ví dụ: Nhận tư vấn')
                                    ->maxLength(255),
                            ]),
                        ]),
                    ]),

                Section::make('Hình ảnh & Hiển thị')
                    ->schema([
                        ToggleButtons::make('image_source')
                            ->label('Nguồn hình ảnh')
                            ->options([
                                'upload' => 'Tải ảnh lên',
                                'url' => 'Dùng đường dẫn ảnh (URL)',
                            ])
                            ->icons([
                                'upload' => Heroicon::OutlinedArrowUpTray,
                                'url' => Heroicon::OutlinedLink,
                            ])
                            ->inline()
                            ->default('upload')
                            ->live()
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->label('Hình ảnh banner')
                            ->helperText('JPG, PNG hoặc WebP, tối đa '.self::maxUploadLabel().'. Nên dùng ảnh ngang, khoảng 1920×1080.')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(self::maxUploadKb())
                            ->imageEditor()
                            ->disk('public')
                            ->directory('banners')
                            ->required(fn (Get $get) => $get('image_source') !== 'url')
                            ->visible(fn (Get $get) => $get('image_source') !== 'url')
                            ->columnSpanFull(),
                        TextInput::make('image_url')
                            ->label('Đường dẫn ảnh')
                            ->placeholder('https://...')
                            ->url()
                            ->maxLength(1000)
                            ->live(onBlur: true)
                            ->required(fn (Get $get) => $get('image_source') === 'url')
                            ->visible(fn (Get $get) => $get('image_source') === 'url')
                            ->columnSpanFull(),
                        View::make('filament.forms.components.banner-image-preview')
                            ->visible(fn (Get $get) => $get('image_source') === 'url' && filled($get('image_url')))
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Kích hoạt hiển thị')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label('Thứ tự sắp xếp')
                            ->helperText('Số nhỏ hiển thị trước (có thể nhập 0). Nhập số đã có banner dùng thì banner này chèn vào vị trí đó, các banner khác tự lùi xuống.')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->required()
                            ->default(fn () => (int) Banner::max('sort_order') + 1),
                    ])->columns(2),

                Section::make('Liên kết nút hành động (Call To Action)')
                    ->description('Đường dẫn của 2 nút bấm trên slide (dùng chung cho mọi ngôn ngữ)')
                    ->schema([
                        TextInput::make('button_primary_url')
                            ->label('Nút chính (Liên kết / URL)')
                            ->placeholder('Ví dụ: /giao-duc-tai-duc'),
                        TextInput::make('button_secondary_url')
                            ->label('Nút phụ (Liên kết / URL)')
                            ->placeholder('Ví dụ: /lien-he'),
                    ])->columns(2),
            ]);
    }

    /** Ảnh là đường dẫn ngoài (http/https) thay vì file đã tải lên. */
    public static function isExternalUrl(?string $image): bool
    {
        return Str::startsWith((string) $image, ['http://', 'https://', '//']);
    }

    /** Khi mở form sửa: ảnh dạng URL => chuyển sang ô "Đường dẫn ảnh". */
    public static function fillImageFields(array $data): array
    {
        if (self::isExternalUrl($data['image'] ?? null)) {
            $data['image_source'] = 'url';
            $data['image_url'] = $data['image'];
            $data['image'] = null;
        } else {
            $data['image_source'] = 'upload';
        }

        return $data;
    }

    /** Khi lưu: gộp 2 ô ảnh về cột "image". */
    public static function saveImageFields(array $data): array
    {
        if (($data['image_source'] ?? 'upload') === 'url') {
            $data['image'] = trim((string) ($data['image_url'] ?? ''));
        }

        unset($data['image_source'], $data['image_url']);

        return $data;
    }

    /** Giới hạn tải lên thực tế của PHP (upload_max_filesize, post_max_size), tính bằng KB, tối đa 10MB. */
    public static function maxUploadKb(): int
    {
        $toKb = function (string $value): int {
            $value = trim($value);
            $number = (float) $value;

            return (int) match (strtolower(substr($value, -1))) {
                'g' => $number * 1024 * 1024,
                'm' => $number * 1024,
                'k' => $number,
                default => $number / 1024,
            };
        };

        $limits = array_filter([
            $toKb((string) ini_get('upload_max_filesize')),
            $toKb((string) ini_get('post_max_size')),
            10 * 1024,
        ]);

        return max(1, min($limits));
    }

    public static function maxUploadLabel(): string
    {
        $kb = self::maxUploadKb();

        return $kb >= 1024 ? rtrim(rtrim(number_format($kb / 1024, 1), '0'), '.').'MB' : $kb.'KB';
    }
}
