<?php

namespace App\Modules\Access\Filament\Resources\Roles\Pages;

use App\Modules\Access\Filament\Resources\Roles\RoleResource;
use App\Modules\Access\Models\Role;
use Filament\Resources\Pages\CreateRecord;

/**
 * Làm gì:   Thêm vai trò mới (không phải vai trò lõi, xóa được).
 * Ai gọi:   RoleResource (nút "Thêm vai trò").
 * Dữ liệu:  bảng roles, role_permission.
 */
class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ['is_system' => false, 'sort_order' => (int) Role::query()->max('sort_order') + 1] + $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
