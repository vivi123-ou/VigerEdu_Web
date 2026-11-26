<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'full_name',
        'username',
        'email',
        'password',
        'role',
        'active',
        'crm_role',
        'must_change_password',
        'crm_team_id',
    ];

    /** Bộ nhớ đệm vai trò CRM trong một request (tránh truy vấn lặp khi kiểm tra quyền nhiều lần) */
    protected ?CrmRole $crmRoleCache = null;

    /** @var array<int, int>|null */
    protected ?array $teamMemberIdsCache = null;

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->username) && ! empty($user->email)) {
                $base = Str::before($user->email, '@');
                $username = $base;
                $counter = 1;
                while (static::where('username', $username)->exists()) {
                    $username = $base.$counter++;
                }
                $user->username = $username;
            }

            if (empty($user->role)) {
                $user->role = 'ADMIN';
            }

            if (! isset($user->active)) {
                $user->active = true;
            }
        });
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['full_name'] = $value;
    }

    public function getNameAttribute(): ?string
    {
        return $this->attributes['full_name'] ?? $this->attributes['username'] ?? null;
    }

    /** Tên hiển thị trong CRM */
    public function displayName(): string
    {
        return (string) ($this->full_name ?: ($this->name ?: $this->username));
    }

    // ------------------------------------------------------------------
    // Phân quyền CRM (bảng crm_roles / crm_role_permission / crm_teams)
    // ------------------------------------------------------------------

    public function crmTeam(): BelongsTo
    {
        return $this->belongsTo(CrmTeam::class, 'crm_team_id');
    }

    /** Key vai trò CRM hiệu lực: crm_role, chưa đặt thì suy từ role cũ (ADMIN của web => super_admin). */
    public function crmRole(): string
    {
        return $this->crm_role ?: ($this->role === 'ADMIN' ? 'super_admin' : 'consultant');
    }

    public function crmRoleModel(): ?CrmRole
    {
        if (! $this->crmRoleCache || $this->crmRoleCache->key !== $this->crmRole()) {
            $this->crmRoleCache = CrmRole::with('permissions')->firstWhere('key', $this->crmRole());
        }

        return $this->crmRoleCache;
    }

    public function crmRoleLabel(): string
    {
        return $this->crmRoleModel()?->name ?? $this->crmRole();
    }

    /** Có quyền CRM không, VD crmCan('leads.delete'). Super Admin luôn có mọi quyền. */
    public function crmCan(string $permission): bool
    {
        $role = $this->crmRoleModel();

        return $role && ($role->is_system || in_array($permission, $role->permissionKeys(), true));
    }

    /** Phạm vi dữ liệu khách: all | team | own (không có vai trò hợp lệ => own) */
    public function crmScope(): string
    {
        return $this->crmRoleModel()?->scope ?? 'own';
    }

    /** Super Admin / Admin (phạm vi toàn bộ + quản lý nhân viên) — dùng cho các quy tắc cấp bậc */
    public function isCrmAdmin(): bool
    {
        return in_array($this->crmRole(), ['super_admin', 'admin'], true);
    }

    /**
     * Id các nhân viên thuộc phạm vi nhóm của user: chính mình + thành viên nhóm mình thuộc về
     * + thành viên các nhóm mình làm trưởng nhóm.
     *
     * @return array<int, int>
     */
    public function teamMemberIds(): array
    {
        if ($this->teamMemberIdsCache !== null) {
            return $this->teamMemberIdsCache;
        }

        $teamIds = CrmTeam::where('manager_id', $this->id)->pluck('id')
            ->when($this->crm_team_id, fn ($c) => $c->push($this->crm_team_id))
            ->unique();

        return $this->teamMemberIdsCache = self::whereIn('crm_team_id', $teamIds)->pluck('id')
            ->push($this->id)->unique()->values()->all();
    }

    /**
     * Nhân viên user này được phép giao khách cho / thấy trong ô chọn tư vấn viên, theo phạm vi:
     * all => mọi người đang hoạt động; team => thành viên nhóm; own => chỉ chính mình.
     *
     * @return Collection<int, string> id => tên hiển thị
     */
    public function assignableUsers(): Collection
    {
        return self::where('active', true)
            ->when($this->crmScope() === 'team', fn ($q) => $q->whereIn('id', $this->teamMemberIds()))
            ->when($this->crmScope() === 'own', fn ($q) => $q->whereKey($this->id))
            ->orderBy('full_name')->get()
            ->mapWithKeys(fn (self $u) => [$u->id => $u->displayName()]);
    }

    /** Có được xem / thao tác khách này không (theo quyền leads.view và phạm vi dữ liệu). */
    public function canSeeLead(Lead $lead): bool
    {
        if (! $this->crmCan('leads.view')) {
            return false;
        }

        return match ($this->crmScope()) {
            'all' => true,
            'team' => $lead->assigned_to === null || in_array($lead->assigned_to, $this->teamMemberIds(), true),
            default => $lead->assigned_to === $this->id,
        };
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() && (bool) $this->active;
    }

    public function getFilamentName(): string
    {
        return (string) ($this->full_name ?: $this->username ?: $this->email);
    }
}
