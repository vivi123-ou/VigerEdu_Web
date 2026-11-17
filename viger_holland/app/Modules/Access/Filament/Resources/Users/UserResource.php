<?php

namespace App\Modules\Access\Filament\Resources\Users;

use App\Models\User;
use App\Modules\Access\Filament\Resources\Users\Pages\CreateUser;
use App\Modules\Access\Filament\Resources\Users\Pages\EditUser;
use App\Modules\Access\Filament\Resources\Users\Pages\ListUsers;
use App\Modules\Access\Filament\Resources\Users\Schemas\UserForm;
use App\Modules\Access\Filament\Resources\Users\Tables\UsersTable;
use App\Modules\Access\Models\Role;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Làm gì:   Trang "Tài khoản": thêm, sửa, khóa tài khoản quản trị viên, tư vấn viên, nhân viên; đặt lại mật khẩu.
 * Ai gọi:   Filament (đường dẫn /admin/users). Quyền: accounts.manage (UserPolicy).
 * Dữ liệu:  bảng users, roles. Không ghi mật khẩu ra log.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?string $navigationLabel = 'Tài khoản';

    protected static ?string $modelLabel = 'tài khoản';

    protected static ?string $pluralModelLabel = 'tài khoản';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public const HELP = 'Mỗi người dùng một tài khoản riêng, không dùng chung. Vai trò quyết định người đó thấy mục nào (sửa quyền ở trang "Vai trò & quyền"). Người nghỉ việc thì tắt "Đang hoạt động" thay vì xóa, để nhật ký vẫn biết ai đã sửa gì. Chỉ quản trị viên mới sửa được tài khoản quản trị viên.';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * Vai trò được chọn khi thêm / sửa tài khoản. Người không phải quản trị viên không thể tạo quản trị viên.
     *
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        $user = auth()->user();

        return Role::query()->where('is_active', true)->orderBy('sort_order')
            ->when(! ($user instanceof User && $user->isAdmin()), fn ($query) => $query->where('code', '!=', User::ROLE_ADMIN))
            ->pluck('name', 'code')->all();
    }
}
