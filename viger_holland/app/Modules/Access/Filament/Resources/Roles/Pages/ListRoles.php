<?php

namespace App\Modules\Access\Filament\Resources\Roles\Pages;

use App\Modules\Access\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Làm gì:   Trang danh sách vai trò, có chữ "Trang này làm gì?".
 * Ai gọi:   RoleResource.
 * Dữ liệu:  bảng roles.
 */
class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected ?string $subheading = RoleResource::HELP;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Thêm vai trò')];
    }
}
