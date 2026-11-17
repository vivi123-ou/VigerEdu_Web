<?php

namespace App\Modules\Access\Filament\Resources\Users\Pages;

use App\Modules\Access\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Làm gì:   Trang danh sách tài khoản, có chữ "Trang này làm gì?".
 * Ai gọi:   UserResource.
 * Dữ liệu:  bảng users.
 */
class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected ?string $subheading = UserResource::HELP;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Thêm tài khoản')];
    }
}
