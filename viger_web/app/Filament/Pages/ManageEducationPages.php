<?php

namespace App\Filament\Pages;

use App\Support\EducationPages;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** Banner đầu trang của các trang con trong menu "Giáo dục tại Đức". */
class ManageEducationPages extends ManageCategoryHeroPages
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Giáo dục tại Đức';

    protected static ?string $title = 'Quản trị banner các trang Giáo dục tại Đức';

    protected static ?string $slug = 'giao-duc-tai-duc';

    protected static ?int $navigationSort = 10;

    protected static string $content = EducationPages::class;

    protected static string $menuName = 'Giáo dục tại Đức';

    protected static string $menuUrl = '/giao-duc-tai-duc';
}
