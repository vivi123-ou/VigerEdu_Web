<?php

namespace App\Modules\Access\Filament\Resources\Roles;

use App\Modules\Access\Filament\Resources\Roles\Pages\CreateRole;
use App\Modules\Access\Filament\Resources\Roles\Pages\EditRole;
use App\Modules\Access\Filament\Resources\Roles\Pages\ListRoles;
use App\Modules\Access\Filament\Resources\Roles\Schemas\RoleForm;
use App\Modules\Access\Filament\Resources\Roles\Tables\RolesTable;
use App\Modules\Access\Models\Role;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Làm gì:   Trang "Vai trò & quyền": thêm vai trò mới, tick quyền cho từng vai trò, không cần sửa code.
 * Ai gọi:   Filament (đường dẫn /admin/roles). Quyền: accounts.manage (RolePolicy).
 * Dữ liệu:  bảng roles, permissions, role_permission.
 */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $slug = 'roles';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?string $navigationLabel = 'Vai trò & quyền';

    protected static ?string $modelLabel = 'vai trò';

    protected static ?string $pluralModelLabel = 'vai trò';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public const HELP = 'Mỗi vai trò là một bộ quyền: tick quyền nào thì người có vai trò đó thấy mục tương ứng trong menu. Quản trị viên luôn có mọi quyền và là người duy nhất được xuất dữ liệu. Vai trò lõi (Quản trị viên, Tư vấn viên, Nhân viên) không xóa được; có thể thêm vai trò mới khi cần.';

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
