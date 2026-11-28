<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Filament\Forms\Components\CKEditor;
use App\Filament\Forms\Components\TranslatableTabs;
use App\Models\Post;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
                                ->afterStateUpdated(fn (Get $get, Set $set, string $operation, ?Post $record) => self::fillSlug($get, $set, $operation, $record)),
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
                            ->helperText('Tự tạo từ tiêu đề Tiếng Việt; bài chỉ có tiếng Anh thì tạo từ tiêu đề English.')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->validationMessages(['unique' => 'Slug này đã được dùng cho bài viết khác.'])
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
                            // Nút bút chì trên ảnh: cắt / xoay ảnh trước khi lưu
                            ->imageEditor()
                            ->imageEditorAspectRatioOptions([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->helperText('Bấm biểu tượng bút chì trên ảnh để cắt hoặc xoay ảnh.')
                            ->columnSpanFull(),
                        ToggleButtons::make('featured_image_fit')
                            ->label('Cách hiển thị ảnh ở trang chi tiết')
                            ->options([
                                'cover' => 'Cắt cho vừa khung',
                                'contain' => 'Hiện ảnh đầy đủ',
                            ])
                            ->icons([
                                'cover' => Heroicon::OutlinedScissors,
                                'contain' => Heroicon::OutlinedArrowsPointingOut,
                            ])
                            ->default('cover')
                            ->inline()
                            ->required()
                            ->helperText('"Hiện ảnh đầy đủ" giữ nguyên toàn bộ ảnh, không bị cắt mất phần nào.')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    /**
     * Tự tạo slug từ tiêu đề: ưu tiên tiêu đề Tiếng Việt, bài chỉ có tiếng Anh thì dùng tiêu đề English.
     * Khi sửa bài đã có slug thì giữ nguyên để không làm hỏng link cũ.
     */
    private static function fillSlug(Get $get, Set $set, string $operation, ?Post $record): void
    {
        if ($operation !== 'create' && filled($record?->slug)) {
            return;
        }

        $title = collect(config('app.supported_locales'))
            ->map(fn (string $locale) => $get("title.{$locale}"))
            ->first(fn ($title) => filled($title));

        if (blank($title)) {
            return;
        }

        $set('slug', self::uniqueSlug(Str::slug($title), $record?->getKey()));
    }

    /** Thêm hậu tố -2, -3... nếu slug đã có bài khác dùng. */
    private static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base;
        $suffix = 2;

        while (Post::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
