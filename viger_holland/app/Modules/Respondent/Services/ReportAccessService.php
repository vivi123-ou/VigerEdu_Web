<?php

namespace App\Modules\Respondent\Services;

use App\Modules\Assessment\Models\TestSession;
use App\Modules\Respondent\Models\Consent;
use App\Modules\Respondent\Models\Respondent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Làm gì:   Sau khi khách nhập đúng mã email: tạo / cập nhật hồ sơ khách (gộp theo email), gắn vào lượt làm bài,
 *           ghi lời đồng ý (nếu admin bật câu đồng ý), đánh dấu lượt đã xác nhận.
 *           Hồ sơ chỉ được tạo / sửa SAU khi xác nhận mã, nên người khác gõ email của mình vào cũng không sửa được hồ sơ.
 * Ai gọi:   ReportController::verify().
 * Dữ liệu:  respondents, consents, test_sessions; giá trị form lấy từ field_values theo crm_key.
 */
class ReportAccessService
{
    public function __construct(private readonly ReportForm $form) {}

    public function complete(TestSession $session, ?string $consentText): Respondent
    {
        $values = $this->form->valuesByCrmKey($session);
        $email = mb_strtolower((string) ($values['email'] ?? ''));

        return DB::transaction(function () use ($session, $values, $email, $consentText): Respondent {
            $respondent = Respondent::withTrashed()->firstOrNew(['email_hash' => Respondent::hashOf($email)]);
            if (! $respondent->exists) {
                $respondent->uuid = (string) Str::uuid();
            }
            $respondent->deleted_at = null; // khách từng bị xóa mềm làm bài lại thì hồ sơ hiện lại
            $respondent->fill([
                'full_name' => (string) ($values['student_name'] ?? ''),
                'email' => $email,
                'phone' => $values['phone'] ?? null,
                'phone_hash' => Respondent::hashOf($values['phone'] ?? null),
                'parent_phone' => $values['parent_phone'] ?? null,
                'parent_phone_hash' => Respondent::hashOf($values['parent_phone'] ?? null),
                'birth_year' => isset($values['birth_year']) ? (int) $values['birth_year'] : null,
                'role' => $values['role'] ?? null,
                'email_verified_at' => now(),
            ])->save();

            if (filled($consentText)) {
                Consent::query()->create([
                    'respondent_id' => $respondent->id,
                    'test_session_id' => $session->id,
                    'version' => setting('report_consent_version') ?: null,
                    'text_snapshot' => $consentText,
                    'agreed_at' => now(),
                ]);
            }

            $session->update([
                'respondent_id' => $respondent->id,
                'status' => TestSession::STATUS_COMPLETED,
                'verified_at' => now(),
                'completed_at' => now(),
            ]);

            return $respondent;
        });
    }
}
