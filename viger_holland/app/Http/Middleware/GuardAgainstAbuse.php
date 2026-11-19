<?php

namespace App\Http\Middleware;

use App\Modules\System\Services\IpGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Làm gì:   Cửa đầu tiên của mọi yêu cầu: IP đang bị chặn → 403; đường dò của hacker → 404 ngay (không chạy tiếp);
 *           quá số yêu cầu / phút cho toàn web → 429. Vi phạm được đếm, đủ ngưỡng thì tự chặn IP (IpGuard).
 *           Chặn sớm, trả chữ thuần, không mở session / database cho yêu cầu xấu.
 * Ai gọi:   bootstrap/app.php (middleware toàn cục, đứng đầu).
 * Dữ liệu:  IpGuard (blocked_ips, cache).
 */
class GuardAgainstAbuse
{
    public function __construct(private readonly IpGuard $guard) {}

    public function handle(Request $request, Closure $next): Response
    {
        $ip = (string) $request->ip();
        if ($ip === '' || $this->guard->isAllowed($ip)) {
            return $next($request);
        }

        if ($this->guard->isBlocked($ip)) {
            return $this->plain('Địa chỉ của bạn tạm thời bị chặn vì truy cập bất thường. Vui lòng thử lại sau.', 403, ['Retry-After' => '3600']);
        }

        if ($this->guard->isProbe($request->getPathInfo())) {
            $this->guard->strike($ip, 'Dò đường hacker hay dò ('.$request->getPathInfo().')', $request->getPathInfo(), (int) config('security.probe_strikes', 5));

            return $this->plain('Không tìm thấy.', 404);
        }

        if (! $this->guard->hit($ip)) {
            $this->guard->strike($ip, 'Gửi quá nhiều yêu cầu trong một phút', $request->getPathInfo());

            return $this->plain('Bạn thao tác quá nhanh. Vui lòng thử lại sau một phút.', 429, ['Retry-After' => '60']);
        }

        return $next($request);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function plain(string $text, int $status, array $headers = []): Response
    {
        return response($text, $status, $headers + ['Content-Type' => 'text/plain; charset=utf-8', 'X-Robots-Tag' => 'noindex, nofollow']);
    }
}
