<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Làm gì:   Nhóm làm việc CRM. Manager (phạm vi "team") thấy khách giao cho thành viên các nhóm mình phụ trách.
 * Ai gọi:   User::teamMemberIds(); trang Vai trò & phân quyền (tab Nhóm); form nhân viên.
 * Dữ liệu:  bảng crm_teams, users.crm_team_id.
 */
class CrmTeam extends Model
{
    protected $fillable = ['name', 'manager_id'];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'crm_team_id');
    }
}
