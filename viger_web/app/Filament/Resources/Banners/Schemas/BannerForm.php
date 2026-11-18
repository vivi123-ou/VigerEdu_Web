<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Filament\Forms\Components\TranslatableTabs;
use App\Models\Banner;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                        FileUpload::make('image')
                            ->label('Hình ảnh banner')
                            ->image()
                            ->disk('public')
                            ->directory('banners')
                            ->required()
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Kích hoạt hiển thị')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label('Thứ tự sắp xếp')
                            ->helperText('Nhập số đã có banner dùng thì banner mới chèn vào vị trí đó, các banner khác tự lùi xuống.')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
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
}
