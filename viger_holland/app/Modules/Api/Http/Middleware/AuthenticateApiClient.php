<?php

namespace App\Modules\Api\Http\Middleware;

use App\Modules\Api\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Làm gì:   Kiểm tra header X-Api-Token (hợp đồng kết nối mục 1): băm SHA-256, tìm theo băm rồi so lại bằng hash_equals.
 *           Sai / thiếu / đã thu hồi → 401; token không có phạm vi cần → 403. Không ghi token vào log.
 * Ai gọi:   routes/api.php (middleware 'api.token:results:read').
 * Dữ liệu:  api_clients (cập nhật last_used_at).
 */
class AuthenticateApiClient
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $token = (string) $request->header('X-Api-Token', '');
        $hash = ApiClient::hashToken($token);
        $client = $token === '' ? null : ApiClient::query()->where('token_hash', $hash)->first();

        if ($client === null || ! hash_equals($client->token_hash, $hash) || ! $client->is_active) {
            return response()->json(['message' => 'Sai hoặc thiếu token.'], 401);
        }
        if (! $client->can($scope)) {
            return response()->json(['message' => 'Token không có quyền này.'], 403);
        }

        if ($client->last_used_at === null || $client->last_used_at->lt(now()->subMinute())) {
            $client->forceFill(['last_used_at' => now()])->saveQuietly();
        }
        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}
