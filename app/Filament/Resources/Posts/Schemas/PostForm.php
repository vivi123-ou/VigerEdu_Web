<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use Filament\Forms\Components\FileUpload;
use App\Filament\Forms\Components\CKEditor;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin bài viết')
                    ->schema([
                        TextInput::make('title')
                            ->label('Tiêu đề bài viết')
                            ->required()
                            ->maxLength(500)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                            ->label('Đường dẫn (Slug)')
                            ->required()
                            ->maxLength(500),
                        Select::make('category_id')
                            ->label('Chuyên mục')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                PostStatus::Published->value => 'Đã xuất bản (Công khai)',
                                PostStatus::Draft->value => 'Bản nháp',
                            ])
                            ->default(PostStatus::Published->value)
                            ->required(),
                        FileUpload::make('featured_image')
                            ->label('Ảnh đại diện bài viết')
                            ->image()
                            ->disk('public')
                            ->directory('posts')
                            ->columnSpanFull(),
                        Textarea::make('excerpt')
                            ->label('Tóm tắt ngắn (Excerpt)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Nội dung chi tiết')
                    ->schema([
                        CKEditor::make('content')
                            ->label('Nội dung bài viết')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
