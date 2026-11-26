<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmRole;
use App\Models\CrmTeam;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Support\CrmPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Làm gì:   Trang "Quản lý nhân viên": danh sách, cấp tài khoản với mật khẩu tạm, sửa thông tin / vai trò / nhóm,
 *           khoá – mở khoá (kèm chuyển khách đang phụ trách), đặt lại mật khẩu.
 * Ai gọi:   route crm.staff.* (quyền staff.manage).
 * Dữ liệu:  bảng users (dùng chung web chính), crm_roles, crm_teams.
 *
 * Quy tắc phạm vi:
 *   - Phạm vi "all" (Super Admin, Admin): quản lý mọi nhân viên. Phạm vi "team" (Manager): chỉ thành viên nhóm mình.
 *   - Chỉ cấp được vai trò thấp hơn mình; Admin cấp được mọi vai trò trừ Super Admin; chỉ Super Admin cấp Super Admin.
 *   - Không ai tự đổi vai trò hay tự khoá tài khoản của mình.
 *   - Nhân viên tạo từ CRM luôn có role = USER => không vào được trang quản trị Filament của web chính.
 */
class StaffController extends Controller
{
    private const OPEN = ['new', 'contacted', 'consulting', 'potential'];

    public function index(Request $request)
    {
        $viewer = $request->user();
        $q = trim((string) $request->query('q'));
        $roles = CrmRole::orderBy('id')->get();
        $role = $roles->firstWhere('key', $request->query('role'))?->key;
        $status = in_array($request->query('status'), ['active', 'locked'], true) ? $request->query('status') : null;

        $scoped = User::query()->when($viewer->crmScope() !== 'all', fn (Builder $b) => $b->whereIn('id', $viewer->teamMemberIds()));

        $staff = (clone $scoped)
            ->with('crmTeam')
            ->when($q !== '', fn (Builder $b) => $b->where(fn (Builder $w) => $w
                ->where('full_name', 'like', "%{$q}%")
                ->orWhere('username', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")))
            ->when($status, fn (Builder $b) => $b->where('active', $status === 'active'))
            ->orderByDesc('active')->orderBy('full_name')
            ->get()
            // Lọc vai trò bằng PHP vì crm_role có thể trống (suy từ role cũ)
            ->when($role, fn ($c) => $c->filter(fn (User $u) => $u->crmRole() === $role))
            ->values();

        $ids = $staff->pluck('id');
        $openLeads = Lead::whereIn('assigned_to', $ids)->whereIn('status', self::OPEN)
            ->selectRaw('assigned_to, count(*) as total')->groupBy('assigned_to')->pluck('total', 'assigned_to');
        $lastActive = LeadActivity::whereIn('user_id', $ids)
            ->selectRaw('user_id, max(created_at) as last_at')->groupBy('user_id')->pluck('last_at', 'user_id');

        return view('crm::staff.index', [
            'staff' => $staff,
            'roles' => $roles,
            'openLeads' => $openLeads,
            'lastActive' => $lastActive,
            'q' => $q,
            'role' => $role,
            'status' => $status,
            'counts' => [
                'active' => (clone $scoped)->where('active', true)->count(),
                'locked' => (clone $scoped)->where('active', false)->count(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        return view('crm::staff.form', $this->formData($request->user(), new User(['crm_role' => 'consultant', 'active' => true])));
    }

    public function store(Request $request)
    {
        $viewer = $request->user();
        $data = $this->validated($request);
        $tempPassword = self::tempPassword();

        // Manager tạo nhân viên => tự vào nhóm của Manager
        if ($viewer->crmScope() !== 'all') {
            $data['crm_team_id'] = CrmTeam::where('manager_id', $viewer->id)->value('id') ?? $viewer->crm_team_id;
        }

        $member = User::create($data + [
            'password' => $tempPassword,
            'role' => 'USER',              // không cấp quyền Filament của web chính
            'active' => true,
            'must_change_password' => true,
        ]);

        CrmAuditLog::record('staff.create', "Tạo tài khoản {$member->username} (vai trò {$member->crmRoleLabel()}).");

        return redirect()->route('crm.staff.index')
            ->with('success', "Đã tạo tài khoản {$member->displayName()} ({$member->username}).")
            ->with('temp_password', ['user' => $member->username, 'password' => $tempPassword]);
    }

    public function edit(Request $request, User $member)
    {
        $this->authorizeMember($request->user(), $member, allowSelf: true);

        return view('crm::staff.form', $this->formData($request->user(), $member));
    }

    public function update(Request $request, User $member)
    {
        $viewer = $request->user();
        $this->authorizeMember($viewer, $member, allowSelf: true);
        $data = $this->validated($request, $member);

        // Không tự đổi vai trò / nhóm của chính mình; Manager không đổi nhóm của người khác
        if ($member->is($viewer)) {
            unset($data['crm_role'], $data['crm_team_id']);
        } elseif ($viewer->crmScope() !== 'all') {
            unset($data['crm_team_id']);
        }

        $oldRole = $member->crmRoleLabel();
        $member->update($data);
        $changed = array_values(array_diff(array_keys($member->getChanges()), ['updated_at']));
        if ($changed) {
            $roleNote = in_array('crm_role', $changed, true) ? " — vai trò {$oldRole} → {$member->crmRoleLabel()}" : '';
            $fieldNames = ['full_name' => 'họ tên', 'username' => 'tên đăng nhập', 'email' => 'email', 'crm_role' => 'vai trò', 'crm_team_id' => 'nhóm'];
            $changedNames = implode(', ', array_map(fn ($f) => $fieldNames[$f] ?? $f, $changed));
            CrmAuditLog::record('staff.update', "Sửa tài khoản {$member->username}: {$changedNames}{$roleNote}.");
        }

        return redirect()->route('crm.staff.index')->with('success', "Đã cập nhật {$member->displayName()}.");
    }

    /** Khoá tài khoản; tuỳ chọn chuyển khách đang chăm sóc cho người khác (ghi lịch sử từng khách). */
    public function lock(Request $request, User $member)
    {
        abort_if($member->is($request->user()), 422, 'Không thể tự khoá tài khoản của mình.');
        $this->authorizeMember($request->user(), $member);

        $data = $request->validate([
            'reassign_to' => ['nullable', Rule::in($request->user()->assignableUsers()->keys()->all()), Rule::notIn([$member->id])],
        ], [], ['reassign_to' => 'người nhận khách']);

        $moved = 0;
        DB::transaction(function () use ($member, $data, &$moved) {
            $member->update(['active' => false]);

            if (! empty($data['reassign_to'])) {
                $to = User::find($data['reassign_to']);
                Lead::where('assigned_to', $member->id)->whereIn('status', self::OPEN)->get()
                    ->each(function (Lead $lead) use ($to, $member, &$moved) {
                        $lead->update(['assigned_to' => $to->id]);
                        $lead->log("Chuyển phụ trách từ {$member->displayName()} sang {$to->displayName()} (tài khoản cũ bị khoá).");
                        $moved++;
                    });
            }
        });

        CrmAuditLog::record('staff.lock', "Khoá tài khoản {$member->username}".($moved ? ", chuyển {$moved} khách" : '').'.');

        return back()->with('success', "Đã khoá {$member->displayName()}.".($moved ? " Đã chuyển {$moved} khách." : ''));
    }

    public function unlock(Request $request, User $member)
    {
        $this->authorizeMember($request->user(), $member);
        $member->update(['active' => true]);

        CrmAuditLog::record('staff.lock', "Mở khoá tài khoản {$member->username}.");

        return back()->with('success', "Đã mở khoá {$member->displayName()}.");
    }

    public function resetPassword(Request $request, User $member)
    {
        $this->authorizeMember($request->user(), $member);
        $tempPassword = self::tempPassword();
        $member->forceFill(['password' => $tempPassword, 'must_change_password' => true])->save();

        CrmAuditLog::record('staff.reset', "Đặt lại mật khẩu tài khoản {$member->username}.");

        return back()
            ->with('success', "Đã đặt lại mật khẩu cho {$member->displayName()}.")
            ->with('temp_password', ['user' => $member->username, 'password' => $tempPassword]);
    }

    /** Vai trò người xem được phép cấp (xem quy tắc ở đầu class). */
    public static function grantableRoles(User $viewer): Collection
    {
        $mine = CrmPermissions::rank($viewer->crmRole());

        return CrmRole::orderBy('id')->get()->filter(fn (CrmRole $r) => match ($viewer->crmRole()) {
            'super_admin' => true,
            'admin' => $r->key !== 'super_admin',
            default => CrmPermissions::rank($r->key) > $mine,
        })->values();
    }

    /** Người xem có quản lý được nhân viên này không (phạm vi nhóm + cấp bậc vai trò). */
    public static function canManage(User $viewer, User $member, bool $allowSelf = false): bool
    {
        if ($member->is($viewer)) {
            return $allowSelf;
        }

        $inScope = $viewer->crmScope() === 'all' || in_array($member->id, $viewer->teamMemberIds(), true);

        return $inScope && self::grantableRoles($viewer)->contains('key', $member->crmRole());
    }

    private function authorizeMember(User $viewer, User $member, bool $allowSelf = false): void
    {
        abort_unless(self::canManage($viewer, $member, $allowSelf), 403, 'Nhân viên này không thuộc phạm vi quản lý của bạn.');
    }

    private function formData(User $viewer, User $member): array
    {
        return [
            'member' => $member,
            'roles' => self::grantableRoles($viewer),
            'teams' => $viewer->crmScope() === 'all' ? CrmTeam::orderBy('name')->pluck('name', 'id') : collect(),
        ];
    }

    private function validated(Request $request, ?User $member = null): array
    {
        $isSelf = $member && $member->is($request->user());

        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', Rule::unique('users', 'username')->ignore($member?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($member?->id)],
            'crm_role' => $isSelf ? ['nullable'] : ['required', Rule::in(self::grantableRoles($request->user())->pluck('key')->all())],
            'crm_team_id' => ['nullable', Rule::exists('crm_teams', 'id')],
        ], [
            'username.regex' => 'Tên đăng nhập chỉ gồm chữ không dấu, số, dấu chấm, gạch ngang, gạch dưới.',
            'crm_role.in' => 'Bạn không được cấp vai trò này.',
        ], [
            'full_name' => 'họ tên', 'username' => 'tên đăng nhập', 'crm_role' => 'vai trò', 'crm_team_id' => 'nhóm',
        ]);
    }

    /** Mật khẩu tạm dễ đọc qua điện thoại: 10 ký tự, bỏ các ký tự dễ nhầm (0/O, 1/l/I). */
    private static function tempPassword(): string
    {
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $pw = collect(range(1, 10))->map(fn () => $chars[random_int(0, strlen($chars) - 1)])->implode('');
        } while (! preg_match('/\d/', $pw) || ! preg_match('/[a-zA-Z]/', $pw));

        return $pw;
    }
}
