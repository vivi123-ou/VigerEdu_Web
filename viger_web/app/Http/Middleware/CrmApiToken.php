<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Làm gì:   Xác thực API CRM bằng header X-Api-Token (HOP_DONG_KET_NOI mục 1).
 *           Mỗi kênh một token; CRM chỉ giữ bản băm SHA-256, so sánh bằng hash_equals.
 * Ai gọi:   route nhóm api/v1 của CRM, VD middleware('crm.token:leads:write').
 * Dữ liệu:  đọc config('crm.api_channels'); gắn tên kênh vào request attribute "crm_channel".
 *
 * TẠM THỜI: kênh & token khai báo trong .env. Giai đoạn C3 chuyển sang bảng `channels` quản lý trên admin.
 */
class CrmApiToken
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $token = (string) $request->header('X-Api-Token');
        $hash = hash('sha256', $token);

        $channel = $token === '' ? null : collect(config('crm.api_channels'))
            ->first(fn ($c) => ! empty($c['token_hash']) && hash_equals($c['token_hash'], $hash));

        if (! $channel) {
            return response()->json(['message' => 'Sai hoặc thiếu token.'], 401);
        }

        // scope "any": chỉ cần token hợp lệ của kênh đang bật (GET /ping)
        if (empty($channel['enabled']) || ($scope !== 'any' && ! in_array($scope, $channel['scopes'] ?? [], true))) {
            return response()->json(['message' => 'Token không có quyền.'], 403);
        }

        $request->attributes->set('crm_channel', $channel['code']);

        return $next($request);
    }
}
