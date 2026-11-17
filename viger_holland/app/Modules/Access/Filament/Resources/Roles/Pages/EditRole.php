<?php

namespace App\Modules\Access\Filament\Resources\Roles\Pages;

use App\Modules\Access\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Làm gì:   Sửa tên, mô tả, quyền của một vai trò. Vai trò Quản trị viên không sửa được (luôn có mọi quyền).
 * Ai gọi:   RoleResource (nút "Sửa").
 * Dữ liệu:  bảng roles, role_permission.
 */
class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
