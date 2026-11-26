<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Làm gì:   Một quyền CRM, VD "leads.delete". Danh mục nạp từ App\Support\CrmPermissions.
 * Ai gọi:   CrmRole::permissions(); trang Vai trò & phân quyền.
 * Dữ liệu:  bảng crm_permissions.
 */
class CrmPermission extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'label', 'group'];
}
