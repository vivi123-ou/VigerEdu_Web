<?php

namespace App\Modules\Access\Filament\Resources\Roles;

use App\Filament\Concerns\UsesAdminMenu;
use App\Modules\Access\Filament\Resources\Roles\Pages\CreateRole;
use App\Modules\Access\Filament\Resources\Roles\Pages\EditRole;
use App\Modules\Access\Filament\Resources\Roles\Pages\ListRoles;
use App\Modules\Access\Filament\Resources\Roles\Schemas\RoleForm;
use App\Modules\Access\Filament\Resources\Roles\Tables\RolesTable;
use App\Modules\Access\Models\Role;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Làm gì:   Trang "Phân quyền": thêm vai trò mới, tick quyền cho từng vai trò, không cần sửa code.
 * Ai gọi:   Filament (đường dẫn /admin/roles). Quyền: accounts.manage (RolePolicy).
 * Dữ liệu:  bảng roles, permissions, role_permission.
 */
class RoleResource extends Resource
{
    use UsesAdminMenu;

    protected static ?string $model = Role::class;

    protected static ?string $slug = 'roles';

    protected static string $menuKey = 'roles';

    protected static ?string $modelLabel = 'vai trò';

    protected static ?string $pluralModelLabel = 'vai trò';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
