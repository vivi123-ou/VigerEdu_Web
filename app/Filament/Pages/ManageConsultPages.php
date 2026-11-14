<?php

namespace App\Filament\Pages;

use App\Support\ConsultPages;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** Banner đầu trang của các trang con trong menu "Tư vấn" (Du học Đại học, Du học Cao học). */
class ManageConsultPages extends ManageCategoryHeroPages
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Tư vấn';

    protected static ?string $title = 'Quản trị banner các trang Tư vấn';

    protected static ?string $slug = 'tu-van';

    protected static ?int $navigationSort = 20;

    protected static string $content = ConsultPages::class;

    protected static string $menuName = 'Tư vấn';

    protected static string $menuUrl = '/tu-van';
}
