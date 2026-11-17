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
use Illuminate\Support\Collection;

/**
 * Làm gì:   Form thêm / sửa vai trò: tên, mô tả, và các việc được làm (quyền) chia theo nhóm, mỗi việc có dòng giải thích.
 *           Quyền "tải dữ liệu khách" hiện khóa 🔒: chỉ quản trị viên có, không tick được cho vai trò khác.
 * Ai gọi:   RoleResource::form(); CreateRole / EditRole lưu các ô tick (permissions_*) vào bảng role_permission.
 * Dữ liệu:  bảng roles, permissions, role_permission.
 */
class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $permissionSections = collect(Permissions::groups())->values()->map(function (string $group, int $index): Section {
            $permissions = Permission::query()->where('group', $group)->orderBy('sort_order')->get();

            return Section::make($group)
                ->icon($index === 0 ? Heroicon::OutlinedExclamationTriangle : null)
                ->iconColor('warning')
                ->compact()
                ->components([
                    CheckboxList::make(self::fieldName($index))->hiddenLabel()
                        ->options(fn () => self::options($permissions))
                        ->descriptions(fn () => $permissions->pluck('description', 'id')->filter()->all())
                        ->disableOptionWhen(fn (string $value) => (int) $value === self::exportId($permissions))
                        ->columns(1),
                ]);
        })->all();

        return $schema->columns(1)->components([
            Section::make('Vai trò')->columns(2)->components([
                TextInput::make('name')->label('Tên vai trò')->required()->maxLength(100),
                TextInput::make('code')->label('Mã vai trò')
                    ->required()->maxLength(20)->regex('/^[A-Z][A-Z0-9_]*$/')->unique(ignoreRecord: true)
                    ->disabled(fn (string $operation) => $operation === 'edit')
                    ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state))
                    ->validationMessages(['regex' => 'Mã vai trò chỉ gồm chữ in hoa không dấu, số và gạch dưới, ví dụ KE_TOAN.'])
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Chữ in hoa không dấu, ví dụ KE_TOAN. Đặt một lần, không đổi được vì tài khoản gắn với mã này.'),
                Textarea::make('description')->label('Mô tả ngắn')->rows(2)->maxLength(255)->columnSpanFull(),
                Toggle::make('is_active')->label('Đang dùng')->default(true)
                    ->helperText('Tắt thì mọi tài khoản có vai trò này không vào được trang quản trị.'),
            ]),
            Section::make('Được làm những việc nào?')
                ->description('Tick việc nào thì mọi người có vai trò này được làm việc ấy. Ai đăng nhập được cũng thấy Trang chủ quản trị, không cần tick.')
                ->components($permissionSections),
        ]);
    }

    /** Tên ô tick của nhóm quyền thứ $index (mỗi nhóm một ô riêng). */
    public static function fieldName(int $index): string
    {
        return 'permissions_'.$index;
    }

    /**
     * Gom các ô tick của mọi nhóm thành danh sách mã quyền để lưu; luôn bỏ quyền tải dữ liệu.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: list<int>}
     */
    public static function extractPermissionIds(array $data): array
    {
        $ids = [];
        foreach (array_keys(Permissions::groups()) as $index) {
            $ids = [...$ids, ...array_map('intval', (array) ($data[self::fieldName($index)] ?? []))];
            unset($data[self::fieldName($index)]);
        }

        $exportId = Permission::query()->where('code', Permissions::EXPORT)->value('id');

        return [$data, array_values(array_unique(array_filter($ids, fn (int $id) => $id !== $exportId)))];
    }

    /**
     * Điền sẵn các ô tick theo quyền hiện có của vai trò.
     *
     * @param  list<int>  $permissionIds
     * @return array<string, list<int>>
     */
    public static function fillPermissionFields(array $permissionIds): array
    {
        $groups = Permission::query()->whereIn('id', $permissionIds)->pluck('group', 'id');
        $fields = [];

        foreach (Permissions::groups() as $index => $group) {
            $fields[self::fieldName($index)] = $groups->filter(fn (?string $g) => $g === $group)->keys()->map(fn ($id) => (int) $id)->values()->all();
        }

        return $fields;
    }

    /**
     * @param  Collection<int, Permission>  $permissions
     * @return array<int, string>
     */
    private static function options(Collection $permissions): array
    {
        return $permissions->mapWithKeys(fn (Permission $permission) => [
            $permission->id => $permission->code === Permissions::EXPORT ? $permission->name.' 🔒' : $permission->name,
        ])->all();
    }

    /**
     * @param  Collection<int, Permission>  $permissions
     */
    private static function exportId(Collection $permissions): int
    {
        return (int) $permissions->firstWhere('code', Permissions::EXPORT)?->id;
    }
}
