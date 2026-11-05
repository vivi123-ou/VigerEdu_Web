<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Chi tiết cấu hình')
                    ->schema([
                        TextInput::make('key')
                            ->label('Mã cấu hình (Key)')
                            ->required()
                            ->disabled(fn (?string $operation) => $operation === 'edit'),
                        TextInput::make('label')
                            ->label('Tên cài đặt hiển thị')
                            ->required(),
                        Select::make('group')
                            ->label('Nhóm cấu hình')
                            ->options([
                                'general' => 'Cài đặt chung',
                                'header' => 'Header / Menu',
                                'footer' => 'Footer / Chân trang',
                                'social' => 'Mạng xã hội',
                                'home' => 'Trang chủ',
                                'contact' => 'Trang Liên hệ',
                            ])
                            ->required(),
                        Select::make('type')
                            ->label('Loại dữ liệu')
                            ->options([
                                'text' => 'Văn bản ngắn (Text)',
                                'textarea' => 'Đoạn văn (Textarea)',
                                'image' => 'Đường dẫn ảnh',
                                'json' => 'Danh sách (JSON)',
                            ])
                            ->default('text')
                            ->required(),
                        Textarea::make('value')
                            ->label('Giá trị cấu hình')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
