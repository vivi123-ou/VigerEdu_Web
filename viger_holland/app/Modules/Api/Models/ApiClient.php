<?php

namespace App\Modules\Api\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Làm gì:   Một ứng dụng được gọi API của Holland (chatbot của Ý, CRM…). Token chỉ hiện một lần lúc cấp; bảng chỉ giữ
 *           SHA-256 của token và 4 ký tự cuối. Thu hồi = tắt is_active.
 * Ai gọi:   AuthenticateApiClient (kiểm tra token), trang "Kết nối API (chatbot)".
 * Dữ liệu:  bảng api_clients.
 */
#[Fillable(['name', 'token_hash', 'token_last4', 'scopes', 'is_active', 'last_used_at', 'created_by'])]
#[Hidden(['token_hash'])]
class ApiClient extends Model
{
    /** Phạm vi quyền: mã => giải thích. */
    public const SCOPES = [
        'results:read' => 'Đọc kết quả Holland của một lượt làm bài (không có họ tên, liên lạc) và danh mục',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['scopes' => 'array', 'is_active' => 'boolean', 'last_used_at' => 'datetime'];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Cấp token mới cho ứng dụng; trả về token gốc (chỉ hiện một lần).
     *
     * @param  list<string>  $scopes
     * @return array{0: self, 1: string}
     */
    public static function issue(string $name, array $scopes, ?int $userId = null): array
    {
        $token = 'hl_'.Str::random(40);
        $client = self::query()->create([
            'name' => $name, 'token_hash' => self::hashToken($token), 'token_last4' => substr($token, -4),
            'scopes' => array_values($scopes), 'is_active' => true, 'created_by' => $userId,
        ]);

        return [$client, $token];
    }

    public function can(string $scope): bool
    {
        return $this->is_active && in_array($scope, $this->scopes ?? [], true);
    }
}
