<?php

namespace App\Modules\Access\Filament\Resources\Roles\Pages;

use App\Modules\Access\Filament\Resources\Roles\RoleResource;
use App\Modules\Access\Filament\Resources\Roles\Schemas\RoleForm;
use App\Modules\Access\Models\Role;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Làm gì:   Thêm vai trò mới (không phải vai trò lõi, xóa được) cùng các việc được làm.
 * Ai gọi:   RoleResource (nút "Thêm vai trò").
 * Dữ liệu:  bảng roles, role_permission.
 */
class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [RoleResource::helpAction()];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        [$data, $permissionIds] = RoleForm::extractPermissionIds($data);

        return DB::transaction(function () use ($data, $permissionIds): Role {
            $role = Role::create($data + ['is_system' => false, 'sort_order' => (int) Role::query()->max('sort_order') + 1]);
            $role->permissions()->sync($permissionIds);

            return $role;
        });
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
