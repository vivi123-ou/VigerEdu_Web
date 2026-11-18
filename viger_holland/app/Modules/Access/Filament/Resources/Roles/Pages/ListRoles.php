<?php

namespace App\Modules\Access\Filament\Resources\Roles\Pages;

use App\Modules\Access\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Làm gì:   Trang danh sách vai trò (Phân quyền), có câu mô tả ngắn và nút "?" hướng dẫn.
 * Ai gọi:   RoleResource.
 * Dữ liệu:  bảng roles.
 */
class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string|Htmlable
    {
        return RoleResource::getNavigationLabel();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return RoleResource::menuItem()['summary'] ?? null;
    }

    protected function getHeaderActions(): array
    {
        return [
            RoleResource::helpAction(),
            CreateAction::make()->label('Thêm vai trò'),
        ];
    }
}
