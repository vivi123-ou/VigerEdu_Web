<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmPermission;
use App\Models\CrmRole;
use App\Models\CrmTeam;
use App\Models\User;
use App\Support\CrmPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Làm gì:   Trang "Vai trò & phân quyền": ma trận quyền (bật / tắt từng quyền cho từng vai trò), phạm vi dữ liệu
 *           của vai trò (toàn bộ / nhóm / được giao), và quản lý nhóm làm việc (trưởng nhóm = Manager).
 * Ai gọi:   route crm.roles.* (quyền roles.manage — mặc định chỉ Super Admin).
 * Dữ liệu:  ghi crm_role_permission, crm_roles.scope, crm_teams. Vai trò hệ thống (Super Admin) không sửa được.
 *           Không cho tự gỡ quyền roles.manage khỏi vai trò của chính mình (tránh khoá mất trang này).
 */
class RoleController extends Controller
{
    public function index()
    {
        return view('crm::roles.index', [
            'roles' => CrmRole::with('permissions')->orderBy('id')->get(),
            'groups' => CrmPermission::orderBy('id')->get()->groupBy('group'),
            'scopes' => CrmPermissions::SCOPES,
            'teams' => CrmTeam::with('manager')->withCount('members')->orderBy('name')->get(),
            'users' => User::where('active', true)->orderBy('full_name')->get(),
            'userCounts' => User::where('active', true)->get()->countBy(fn (User $u) => $u->crmRole()),
        ]);
    }

    public function update(Request $request)
    {
        $editable = CrmRole::where('is_system', false)->get();
        $permIds = CrmPermission::pluck('id', 'key');

        $data = $request->validate([
            'perms' => ['array'],
            'perms.*' => ['array'],
            'perms.*.*' => [Rule::in($permIds->keys()->all())],
            'scope' => ['array'],
            'scope.*' => [Rule::in(array_keys(CrmPermissions::SCOPES))],
        ]);

        $myRole = $request->user()->crmRole();
        $before = $editable->load('permissions')->mapWithKeys(fn ($r) => [$r->key => [$r->permissionKeys(), $r->scope]]);

        DB::transaction(function () use ($editable, $permIds, $data, $myRole) {
            foreach ($editable as $role) {
                $keys = $data['perms'][$role->key] ?? [];

                // Giữ quyền roles.manage của chính vai trò người đang sửa
                if ($role->key === $myRole && ! in_array('roles.manage', $keys, true)) {
                    $keys[] = 'roles.manage';
                }

                $role->permissions()->sync(collect($keys)->map(fn ($k) => $permIds[$k])->all());

                if (isset($data['scope'][$role->key])) {
                    $role->update(['scope' => $data['scope'][$role->key]]);
                }
            }
        });

        // Nhật ký ghi tên quyền / phạm vi bằng tiếng Việt (người xem nhật ký không cần biết mã quyền)
        $permLabel = fn (array $keys) => implode(', ', array_map(fn ($k) => CrmPermissions::PERMISSIONS[$k][0] ?? $k, $keys));
        $scopeLabel = fn (string $s) => CrmPermissions::SCOPES[$s] ?? $s;
        $changes = CrmRole::with('permissions')->where('is_system', false)->get()->map(function (CrmRole $r) use ($before, $permLabel, $scopeLabel) {
            [$oldKeys, $oldScope] = $before[$r->key];
            $parts = array_filter([
                ($add = array_diff($r->permissionKeys(), $oldKeys)) ? 'thêm quyền '.$permLabel($add) : null,
                ($del = array_diff($oldKeys, $r->permissionKeys())) ? 'bỏ quyền '.$permLabel($del) : null,
                $oldScope !== $r->scope ? 'phạm vi “'.$scopeLabel($oldScope).'” → “'.$scopeLabel($r->scope).'”' : null,
            ]);

            return $parts ? $r->name.': '.implode('; ', $parts) : null;
        })->filter();
        if ($changes->isNotEmpty()) {
            CrmAuditLog::record('roles.update', 'Đổi phân quyền — '.$changes->implode(' | '));
        }

        return redirect()->route('crm.roles.index')->with('success', 'Đã lưu ma trận phân quyền.');
    }

    public function storeTeam(Request $request)
    {
        CrmTeam::create($this->validatedTeam($request));

        CrmAuditLog::record('teams.change', 'Thêm nhóm làm việc.');

        return redirect()->route('crm.roles.index', ['tab' => 'teams'])->with('success', 'Đã thêm nhóm.');
    }

    public function updateTeam(Request $request, CrmTeam $team)
    {
        $team->update($this->validatedTeam($request, $team));

        CrmAuditLog::record('teams.change', "Cập nhật nhóm {$team->name}.");

        return redirect()->route('crm.roles.index', ['tab' => 'teams'])->with('success', "Đã cập nhật nhóm {$team->name}.");
    }

    public function destroyTeam(CrmTeam $team)
    {
        $team->delete();   // thành viên: crm_team_id => null (khoá ngoại nullOnDelete)

        CrmAuditLog::record('teams.change', "Xoá nhóm {$team->name}.");

        return redirect()->route('crm.roles.index', ['tab' => 'teams'])->with('success', "Đã xoá nhóm {$team->name}.");
    }

    private function validatedTeam(Request $request, ?CrmTeam $team = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('crm_teams', 'name')->ignore($team?->id)],
            'manager_id' => ['nullable', Rule::exists('users', 'id')->where('active', true)],
        ], [], ['name' => 'tên nhóm', 'manager_id' => 'trưởng nhóm']);
    }
}
