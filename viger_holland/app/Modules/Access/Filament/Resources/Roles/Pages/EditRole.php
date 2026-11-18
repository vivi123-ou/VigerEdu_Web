<?php

namespace App\Modules\Access\Filament\Resources\Roles\Pages;

use App\Modules\Access\Filament\Resources\Roles\RoleResource;
use App\Modules\Access\Filament\Resources\Roles\Schemas\RoleForm;
use App\Modules\Access\Models\Role;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Làm gì:   Sửa tên, mô tả, việc được làm của một vai trò. Vai trò Quản trị viên không sửa được (luôn có mọi quyền).
 * Ai gọi:   RoleResource (nút "Sửa").
 * Dữ liệu:  bảng roles, role_permission.
 */
class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->name.' được làm gì?';
    }

    protected function getHeaderActions(): array
    {
        return [RoleResource::helpAction(), DeleteAction::make()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $role */
        $role = $this->getRecord();

        return $data + RoleForm::fillPermissionFields($role->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->all());
    }

    /**
     * @param  Role  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        [$data, $permissionIds] = RoleForm::extractPermissionIds($data);

        return DB::transaction(function () use ($record, $data, $permissionIds): Role {
            $record->update($data);
            $record->permissions()->sync($permissionIds);

            return $record;
        });
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
