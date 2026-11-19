<?php

namespace App\Modules\Respondent\Services;

use App\Modules\Assessment\Models\TestSession;
use App\Modules\Respondent\Models\Respondent;
use Illuminate\Support\Collection;

/**
 * Làm gì:   Tìm báo cáo của MỘT khách theo đúng số điện thoại (của học sinh hoặc phụ huynh), email, hoặc mã báo cáo 8 ký tự.
 *           Chỉ khớp đúng (so bằng cột băm), gõ thiếu không ra gợi ý khách khác: tư vấn viên không dò được danh sách.
 * Ai gọi:   trang Filament "Tra cứu báo cáo khách".
 * Dữ liệu:  respondents (email_hash, phone_hash, parent_phone_hash), test_sessions (đã nhập đúng mã).
 */
class ReportLookup
{
    /**
     * @return array{type: string|null, sessions: Collection<int, TestSession>}
     */
    public function find(string $query): array
    {
        $query = trim($query);
        $sessions = TestSession::query()->whereNotNull('verified_at')->with('respondent')->latest('verified_at');

        if (str_contains($query, '@')) {
            $respondent = Respondent::query()->where('email_hash', Respondent::hashOf(mb_strtolower($query)))->first();

            return ['type' => 'email', 'sessions' => $respondent ? $sessions->where('respondent_id', $respondent->id)->get() : collect()];
        }

        if (preg_match('/^[0-9a-fA-F]{8}$/', $query) && ! preg_match('/^0\d{7}$/', $query)) {
            return ['type' => 'code', 'sessions' => $sessions->where('uuid', 'like', strtolower($query).'%')->get()];
        }

        $phone = ReportForm::normalizePhone($query);
        if (preg_match('/^0\d{9,10}$/', $phone)) {
            $hash = Respondent::hashOf($phone);
            $ids = Respondent::query()->where('phone_hash', $hash)->orWhere('parent_phone_hash', $hash)->pluck('id');

            return ['type' => 'phone', 'sessions' => $ids->isEmpty() ? collect() : $sessions->whereIn('respondent_id', $ids)->get()];
        }

        return ['type' => null, 'sessions' => collect()];
    }
}
