<?php

namespace App\Modules\System\Services;

use App\Models\AdminActivity;
use App\Modules\System\Models\BlockedIp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Làm gì:   Chống truy cập bất thường theo IP (ý Thầy 18/11):
 *           - IP đang bị chặn → từ chối ngay (đọc cache, không tốn truy vấn mỗi lần);
 *           - dò đường hacker hay dò (/wp-admin, /.env, phpmyadmin…) → 404 + tính vi phạm;
 *           - quá số yêu cầu / phút cho toàn web → 429 + tính vi phạm;
 *           - đủ số vi phạm trong 10 phút → tự chặn IP một thời gian, ghi "Lịch sử thao tác" và log (không ghi dữ liệu khách).
 *           IP trong danh sách cho phép (văn phòng) không bao giờ bị chặn. Con số lấy ở "Cài đặt hệ thống", mặc định config/security.php.
 * Ai gọi:   App\Http\Middleware\GuardAgainstAbuse (mọi yêu cầu), trang "IP bị chặn".
 * Dữ liệu:  blocked_ips, cache (đếm), settings.
 */
class IpGuard
{
    public function isAllowed(string $ip): bool
    {
        $list = preg_split('/[\s,]+/', (string) setting('security_allowlist', '')) ?: [];
        $list = array_values(array_filter(array_map('trim', $list)));

        return $list !== [] && IpUtils::checkIp($ip, $list);
    }

    public function isBlocked(string $ip): bool
    {
        return Cache::remember(self::cacheKey($ip), 60, fn () => BlockedIp::query()->active()->where('ip', $ip)->exists());
    }

    public function isProbe(string $path): bool
    {
        $path = '/'.ltrim($path, '/');
        foreach ((array) config('security.probe_patterns') as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Đếm một yêu cầu cho giới hạn chung; false = đã vượt.
     */
    public function hit(string $ip): bool
    {
        $limit = max(30, (int) setting('security_requests_per_minute', (string) config('security.requests_per_minute')));
        $key = 'ipguard:req:'.$ip;
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return false;
        }
        RateLimiter::hit($key, 60);

        return true;
    }

    /**
     * Tính vi phạm; đủ ngưỡng thì chặn. Trả true nếu IP vừa bị chặn.
     */
    public function strike(string $ip, string $reason, string $path, int $points = 1): bool
    {
        if ($this->isAllowed($ip)) {
            return false;
        }
        $key = 'ipguard:strikes:'.$ip;
        $strikes = (int) Cache::get($key, 0) + $points;
        Cache::put($key, $strikes, now()->addMinutes(10));

        if ($strikes < max(1, (int) config('security.strikes_to_block'))) {
            return false;
        }
        Cache::forget($key);
        $this->block($ip, $reason, null, $path, $strikes);

        return true;
    }

    public function block(string $ip, string $reason, ?int $minutes, ?string $path = null, int $hits = 0, ?int $userId = null): BlockedIp
    {
        $minutes ??= max(1, (int) setting('security_block_minutes', (string) config('security.block_minutes')));
        $blocked = BlockedIp::query()->updateOrCreate(['ip' => $ip], [
            'reason' => mb_substr($reason, 0, 255), 'hits' => $hits, 'last_path' => $path ? mb_substr(strtok($path, '?'), 0, 255) : null,
            'blocked_until' => $minutes > 0 ? now()->addMinutes($minutes) : null, 'blocked_by' => $userId,
        ]);
        Cache::put(self::cacheKey($ip), true, 60);

        $who = $userId ? 'Chặn' : 'Hệ thống tự chặn';
        AdminActivity::record(AdminActivity::ACTION_CREATE, 'security.ip', $blocked->id,
            "{$who} IP {$ip}: {$reason}".($minutes > 0 ? " ({$minutes} phút)" : ' (đến khi bỏ chặn)'));
        Log::warning('Chặn IP', ['ip' => $ip, 'reason' => $reason, 'path' => $blocked->last_path]);

        return $blocked;
    }

    public function unblock(BlockedIp $blocked): void
    {
        $blocked->delete();
        Cache::forget(self::cacheKey($blocked->ip));
        RateLimiter::clear('ipguard:req:'.$blocked->ip);
        AdminActivity::record(AdminActivity::ACTION_DELETE, 'security.ip', $blocked->id, 'Bỏ chặn IP '.$blocked->ip);
    }

    private static function cacheKey(string $ip): string
    {
        return 'ipguard:blocked:'.$ip;
    }
}
