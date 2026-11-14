<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Forms\Components\CKEditor;
use App\Filament\Forms\Components\TranslatableTabs;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nội dung trang')
                    ->description('Nhập nội dung cho từng ngôn ngữ. Bản tiếng Anh để trống sẽ hiển thị bản tiếng Việt.')
                    ->schema([
                        TranslatableTabs::make(fn (string $locale, bool $isPrimary) => [
                            TextInput::make("title.{$locale}")
                                ->label('Tiêu đề trang')
                                ->required($isPrimary)
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set) use ($isPrimary) {
                                    if ($isPrimary) {
                                        $set('slug', Str::slug($state));
                                    }
                                }),
                            TextInput::make("subtitle.{$locale}")
                                ->label('Phụ đề trang')
                                ->maxLength(255),
                            CKEditor::make("content.{$locale}")
                                ->label('Nội dung trang'),
                            Fieldset::make('Các khối nội dung / Accordion')
                                ->schema([
                                    Repeater::make("sections.{$locale}")
                                        ->hiddenLabel()
                                        ->helperText('Dành cho trang có danh sách cam kết, FAQ hoặc các khối nội dung lặp lại (như trang Về chúng tôi)')
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
                            Textarea::make("meta_description.{$locale}")
                                ->label('Mô tả tìm kiếm SEO (Meta Description)')
                                ->rows(2)
                                ->maxLength(500),
                        ]),
                    ]),

                Section::make('Thông tin chung')
                    ->schema([
                        TextInput::make('slug')
                            ->label('Đường dẫn (Slug)')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Ví dụ: "ve-chung-toi", "giao-duc-tai-duc", "tu-van"'),
                        Toggle::make('is_published')
                            ->label('Xuất bản công khai')
                            ->default(true),
                        FileUpload::make('banner_image')
                            ->label('Ảnh bìa / Banner đầu trang')
                            ->image()
                            ->disk('public')
                            ->directory('pages')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
