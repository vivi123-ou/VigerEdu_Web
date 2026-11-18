<?php

namespace App\Modules\CrmSync\Models;

use App\Modules\Assessment\Models\TestSession;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Làm gì:   Một gói chờ gửi / đã gửi sang CRM (lead/2 hoặc events/1). Nội dung gói mã hóa; event_id giữ nguyên khi gửi lại.
 * Ai gọi:   CrmSync (tạo), SendToCrm (gửi), trang "Gửi khách sang CRM" (xem, gửi lại), lệnh crm:retry.
 * Dữ liệu:  bảng crm_outbox. last_error không chứa dữ liệu cá nhân.
 */
#[Table('crm_outbox')]
#[Fillable(['event_id', 'test_session_id', 'schema', 'payload', 'status', 'tries', 'next_try_at', 'last_http_status', 'last_error', 'sent_at'])]
class CrmOutbox extends Model
{
    public const PENDING = 'pending';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    public const DISABLED = 'disabled';

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        self::PENDING => 'Chờ gửi',
        self::SENT => 'Đã gửi',
        self::FAILED => 'Lỗi',
        self::DISABLED => 'Đang tắt kết nối',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'next_try_at' => 'datetime',
            'sent_at' => 'datetime',
            'tries' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<TestSession, $this>
     */
    public function testSession(): BelongsTo
    {
        return $this->belongsTo(TestSession::class);
    }
}
