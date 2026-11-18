<?php

namespace App\Modules\CrmSync\Console;

use App\Modules\Assessment\Models\TestSession;
use App\Modules\CrmSync\Services\LeadPayload;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Làm gì:   In gói lead/2 của một lượt làm bài, ĐÃ CHE họ tên, email, số điện thoại, để người làm CRM biết dữ liệu sẽ nhận.
 *           Không gửi gì, không ghi vào hàng đợi.
 * Ai gọi:   gõ tay: php artisan crm:payload B61B8A81 (mã báo cáo 8 ký tự).
 * Dữ liệu:  test_sessions và các bảng LeadPayload đọc.
 */
#[Signature('crm:payload {ma : Mã báo cáo 8 ký tự}')]
#[Description('In gói gửi CRM (đã che dữ liệu cá nhân) của một lượt làm bài')]
class ShowCrmPayload extends Command
{
    public function handle(LeadPayload $lead): int
    {
        $code = strtolower((string) $this->argument('ma'));
        $session = preg_match('/^[0-9a-f]{8}$/', $code)
            ? TestSession::query()->whereNotNull('verified_at')->where('uuid', 'like', $code.'%')->first()
            : null;

        if ($session === null) {
            $this->error('Không tìm thấy lượt làm bài đã nhập đúng mã với mã báo cáo này.');

            return self::FAILURE;
        }

        $payload = LeadPayload::masked($lead->build($session, (string) Str::uuid()));
        $this->line((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
