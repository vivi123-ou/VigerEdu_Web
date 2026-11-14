<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Filament\Forms\Components\CKEditor;
use App\Filament\Forms\Components\TranslatableTabs;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nội dung bài viết')
                    ->description('Bài chỉ hiển thị ở ngôn ngữ có tiêu đề và nội dung: điền tab Tiếng Việt để hiện ở chế độ tiếng Việt, tab English để hiện ở chế độ tiếng Anh. Bài cho chuyên mục tiếng Anh chỉ cần điền tab English.')
                    ->schema([
                        TranslatableTabs::make(fn (string $locale, bool $isPrimary) => [
                            TextInput::make("title.{$locale}")
                                ->label('Tiêu đề bài viết')
                                // Cần tiêu đề ở ít nhất một ngôn ngữ
                                ->required(fn (Get $get) => collect(config('app.supported_locales'))
                                    ->every(fn (string $otherLocale) => $otherLocale === $locale || blank($get("title.{$otherLocale}"))))
                                ->validationMessages(['required' => 'Cần nhập tiêu đề ở ít nhất một ngôn ngữ.'])
                                ->maxLength(500)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Get $get, Set $set) use ($isPrimary) {
                                    if ($isPrimary || blank($get('slug'))) {
                                        $set('slug', Str::slug($state));
                                    }
                                }),
                            Textarea::make("excerpt.{$locale}")
                                ->label('Tóm tắt ngắn (Excerpt)')
                                ->rows(3),
                            CKEditor::make("content.{$locale}")
                                ->label('Nội dung bài viết')
                                // Đã có tiêu đề ở ngôn ngữ này thì phải có nội dung
                                ->required(fn (Get $get) => filled($get("title.{$locale}"))),
                        ]),
                    ]),

                Section::make('Thông tin bài viết')
                    ->schema([
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
                    ])->columns(2),
            ]);
    }
}
