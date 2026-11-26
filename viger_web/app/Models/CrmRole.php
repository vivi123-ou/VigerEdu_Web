<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Làm gì:   Một vai trò CRM (Super Admin, Admin, Manager, Consultant, Marketing…) và các quyền của nó.
 * Ai gọi:   User::crmRoleModel() / crmCan(); trang Vai trò & phân quyền.
 * Dữ liệu:  bảng crm_roles, crm_role_permission, crm_permissions.
 */
class CrmRole extends Model
{
    protected $fillable = ['key', 'name', 'description', 'scope', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(CrmPermission::class, 'crm_role_permission', 'role_id', 'permission_id');
    }

    /** @return array<int, string> */
    public function permissionKeys(): array
    {
        return $this->permissions->pluck('key')->all();
    }
}
