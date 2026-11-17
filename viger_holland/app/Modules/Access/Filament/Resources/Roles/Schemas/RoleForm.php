<?php

namespace App\Modules\Access\Filament\Resources\Roles\Schemas;

use App\Modules\Access\Models\Permission;
use App\Modules\Access\Permissions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Làm gì:   Form thêm / sửa vai trò và tick quyền. Quyền "xuất dữ liệu" không cấp được cho vai trò khác ngoài quản trị viên.
 * Ai gọi:   RoleResource::form().
 * Dữ liệu:  bảng roles, permissions, role_permission.
 */
class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Vai trò')->columns(2)->components([
                TextInput::make('name')->label('Tên vai trò')->required()->maxLength(100),
                TextInput::make('code')->label('Mã vai trò')
                    ->required()->maxLength(20)->regex('/^[A-Z][A-Z0-9_]*$/')->unique(ignoreRecord: true)
                    ->disabled(fn (string $operation) => $operation === 'edit')
                    ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state))
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Chữ in hoa không dấu, ví dụ KE_TOAN. Đặt một lần, không đổi được vì tài khoản gắn với mã này.'),
                Textarea::make('description')->label('Mô tả')->rows(2)->maxLength(500)->columnSpanFull(),
                Toggle::make('is_active')->label('Đang dùng')->default(true)
                    ->helperText('Tắt thì mọi tài khoản có vai trò này không vào được trang quản trị.'),
            ]),

            Section::make('Quyền')
                ->description('Tick quyền nào thì vai trò thấy mục đó. "'.Permissions::ALL[Permissions::EXPORT][0].'" chỉ dành cho quản trị viên nên không có ở đây.')
                ->components([
                    CheckboxList::make('permissions')->hiddenLabel()
                        ->relationship('permissions', 'name',
                            modifyQueryUsing: fn (Builder $query) => $query->where('code', '!=', Permissions::EXPORT)->orderBy('sort_order'))
                        ->getOptionLabelFromRecordUsing(fn (Permission $record) => $record->group.' – '.$record->name)
                        ->columns(2)
                        ->bulkToggleable(),
                ]),
        ]);
    }
}
