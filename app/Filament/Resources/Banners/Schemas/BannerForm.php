<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin Banner')
                    ->description('Tiêu đề và nội dung hiển thị trên slider')
                    ->schema([
                        TextInput::make('title')
                            ->label('Tiêu đề chính')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('eyebrow')
                            ->label('Dòng phụ đề nhỏ (Eyebrow)')
                            ->placeholder('Ví dụ: Du học Đức')
                            ->maxLength(255),
                        Textarea::make('desc')
                            ->label('Mô tả chi tiết')
                            ->rows(3)
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->label('Hình ảnh banner')
                            ->image()
                            ->disk('public')
                            ->directory('banners')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('alt')
                            ->label('Mô tả hình ảnh (Alt text)')
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label('Kích hoạt hiển thị')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label('Thứ tự sắp xếp')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                Section::make('Nút hành động (Call To Action)')
                    ->description('Cấu hình 2 nút bấm trên slide')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('button_primary_label')
                                ->label('Nút chính (Tên nhãn)')
                                ->placeholder('Ví dụ: Khám phá ngay'),
                            TextInput::make('button_primary_url')
                                ->label('Nút chính (Liên kết / URL)')
                                ->placeholder('Ví dụ: /giao-duc-tai-duc'),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('button_secondary_label')
                                ->label('Nút phụ (Tên nhãn)')
                                ->placeholder('Ví dụ: Nhận tư vấn'),
                            TextInput::make('button_secondary_url')
                                ->label('Nút phụ (Liên kết / URL)')
                                ->placeholder('Ví dụ: /lien-he'),
                        ]),
                    ]),
            ]);
    }
}
