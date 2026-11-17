<?php

namespace Database\Seeders;

use App\Modules\Access\Models\Permission;
use App\Modules\Access\Models\Role;
use App\Modules\Access\Permissions;
use Illuminate\Database\Seeder;

/**
 * Làm gì:   Nạp danh mục quyền và 4 vai trò có sẵn (Quản trị viên, Tư vấn viên, Nhân viên, Marketing).
 * Ai gọi:   DatabaseSeeder. Chạy lại không trùng; vai trò đã có thì giữ nguyên quyền admin đã sửa.
 * Dữ liệu:  bảng permissions, roles, role_permission.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $sort = 0;
        foreach (Permissions::ALL as $code => [$name, $group]) {
            Permission::firstOrCreate(['code' => $code], ['name' => $name, 'group' => $group, 'sort_order' => ++$sort]);
        }

        $sort = 0;
        foreach (Permissions::DEFAULT_ROLES as $code => [$name, $description, $permissions, $isSystem]) {
            $role = Role::firstOrCreate(['code' => $code], [
                'name' => $name, 'description' => $description, 'is_system' => $isSystem, 'is_active' => true, 'sort_order' => ++$sort,
            ]);

            if ($role->wasRecentlyCreated) {
                $ids = $permissions === ['*']
                    ? Permission::query()->pluck('id')
                    : Permission::query()->whereIn('code', $permissions)->pluck('id');
                $role->permissions()->sync($ids);
            }
        }
    }
}
