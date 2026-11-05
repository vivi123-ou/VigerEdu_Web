<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use App\Filament\Forms\Components\CKEditor;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin chung')
                    ->schema([
                        TextInput::make('title')
                            ->label('Tiêu đề trang')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                            ->label('Đường dẫn (Slug)')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Ví dụ: "ve-chung-toi", "giao-duc-tai-duc", "tu-van"'),
                        TextInput::make('subtitle')
                            ->label('Phụ đề trang')
                            ->maxLength(255),
                        FileUpload::make('banner_image')
                            ->label('Ảnh bìa / Banner đầu trang')
                            ->image()
                            ->disk('public')
                            ->directory('pages'),
                        Toggle::make('is_published')
                            ->label('Xuất bản công khai')
                            ->default(true),
                    ])->columns(2),

                Section::make('Nội dung chính')
                    ->schema([
                        CKEditor::make('content')
                            ->label('Nội dung trang')
                            ->columnSpanFull(),
                    ]),

                Section::make('Các khối nội dung / Accordion')
                    ->description('Dành cho trang có danh sách cam kết, FAQ hoặc các khối nội dung lặp lại (như trang Về chúng tôi)')
                    ->schema([
                        Repeater::make('sections')
                            ->label('Danh sách các mục')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Tiêu đề mục')
                                    ->required(),
                                Textarea::make('body')
                                    ->label('Nội dung mục')
                                    ->rows(3)
                                    ->required(),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),

                Section::make('Tối ưu hóa tìm kiếm (SEO)')
                    ->schema([
                        Textarea::make('meta_description')
                            ->label('Mô tả tìm kiếm (Meta Description)')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
