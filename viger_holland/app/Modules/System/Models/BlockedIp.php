<?php

namespace App\Modules\System\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Làm gì:   Một địa chỉ IP đang bị chặn (tự chặn vì truy cập bất thường, hoặc quản trị viên chặn tay).
 * Ai gọi:   App\Modules\System\Services\IpGuard, trang "IP bị chặn", holland:prune (xóa dòng hết hạn).
 * Dữ liệu:  bảng blocked_ips.
 */
#[Fillable(['ip', 'reason', 'hits', 'last_path', 'blocked_until', 'blocked_by'])]
class BlockedIp extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['blocked_until' => 'datetime'];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('blocked_until')->orWhere('blocked_until', '>', now()));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }
}
