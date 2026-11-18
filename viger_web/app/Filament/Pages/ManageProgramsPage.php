<?php

namespace App\Filament\Pages;

use App\Support\ProgramsPage;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

/** Trang "Chương trình học": banner đầu trang và khối kêu gọi cuối trang. */
class ManageProgramsPage extends ManageCategoryHeroPages
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Chương trình học';

    protected static ?string $title = 'Quản trị giao diện trang Chương trình học';

    protected static ?string $slug = 'chuong-trinh-hoc';

    protected static ?int $navigationSort = 30;

    protected static string $content = ProgramsPage::class;

    protected static string $menuName = 'Chương trình học';

    protected static string $menuUrl = '/chuong-trinh-hoc';

    protected function extraSchema(string $id): array
    {
        return [
            Section::make('Khối kêu gọi cuối trang')
                ->description('Hiển thị bên dưới danh sách bài viết.')
                ->schema([
                    TextInput::make('programs_cta_pretitle')->label('Nhãn nhỏ')->maxLength(100),
                    TextInput::make('programs_cta_title')->label('Tiêu đề')->maxLength(255),
                    Textarea::make('programs_cta_description')->label('Mô tả')->rows(2)->columnSpanFull(),
                    TextInput::make('programs_cta_button_text')->label('Nút: chữ')->maxLength(100),
                    $this->shared(TextInput::make('programs_cta_button_url'))->label('Nút: liên kết')->maxLength(255),
                ])->columns(2),
        ];
    }
}
