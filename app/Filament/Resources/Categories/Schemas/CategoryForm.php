<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin chuyên mục & Menu')
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên chuyên mục')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                            ->label('Đường dẫn (Slug)')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Đường dẫn định danh chuyên mục, ví dụ: "tong-quan", "he-thong-giao-duc"'),
                        Select::make('menu_group')
                            ->label('Nhóm hiển thị trên Header')
                            ->options([
                                'giao-duc-tai-duc' => 'Menu Giáo dục tại Đức',
                                'tu-van' => 'Menu Tư vấn',
                                'blog' => 'Menu Blog',
                                'main' => 'Menu chính (mục riêng, có thể có dropdown)',
                            ])
                            ->placeholder('Chọn nhóm trên menu (nếu muốn hiển thị trên header)')
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state !== 'main') {
                                    $set('parent_id', null);
                                }
                                // Chọn nhóm menu => mặc định bật hiển thị trên menu; bỏ chọn => tắt
                                $set('is_in_menu', filled($state));
                            }),
                        Select::make('parent_id')
                            ->label('Thuộc dropdown của mục')
                            ->helperText('Bỏ trống: hiển thị là một mục trên thanh menu. Chọn mục cha: hiển thị trong dropdown của mục đó (mục cha sẽ tự có dropdown).')
                            ->options(fn (?Category $record) => Category::query()
                                ->where('menu_group', 'main')
                                ->whereNull('parent_id')
                                ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                                ->orderBy('sort_order')
                                ->pluck('name', 'id'))
                            ->placeholder('— Mục cấp 1 (trên thanh menu) —')
                            ->nullable()
                            ->visible(fn (Get $get) => $get('menu_group') === 'main')
                            // Mục đang có mục con thì không được làm mục con (chỉ hỗ trợ 1 cấp dropdown)
                            ->disabled(fn (?Category $record) => $record?->children()->exists() ?? false),
                        TextInput::make('url')
                            ->label('Đường dẫn tùy chỉnh (URL)')
                            ->placeholder('/chuong-trinh-hoc hoặc https://...')
                            ->helperText(fn (Get $get) => 'Bỏ trống: dẫn tới trang danh sách bài viết của chuyên mục'
                                .(isset(Category::GROUP_PREFIXES[(string) $get('menu_group')])
                                    ? ' ('.Category::GROUP_PREFIXES[(string) $get('menu_group')].'/'.($get('slug') ?: 'slug').').'
                                    : '.'))
                            ->maxLength(255)
                            ->visible(fn (Get $get) => filled($get('menu_group'))),
                        Toggle::make('is_in_menu')
                            ->label('Hiển thị trên Menu Header')
                            ->helperText('Tắt để ẩn khỏi menu nhưng vẫn giữ chuyên mục.')
                            ->default(true)
                            ->visible(fn (Get $get) => filled($get('menu_group'))),
                        TextInput::make('sort_order')
                            ->label('Thứ tự sắp xếp')
                            ->helperText('Số nhỏ hiển thị trước. Để trống: xếp cuối nhóm.')
                            ->numeric()
                            ->visible(fn (Get $get) => filled($get('menu_group'))),
                        Textarea::make('description')
                            ->label('Mô tả chuyên mục')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
