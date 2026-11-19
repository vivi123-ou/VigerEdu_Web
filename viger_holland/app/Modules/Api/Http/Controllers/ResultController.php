<?php

namespace App\Modules\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Support\CustomerReport;
use App\Modules\CrmSync\Services\LeadPayload;
use App\Modules\Respondent\Support\InternalReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Làm gì:   GET /api/v1/results/{mã lượt}?view=khach|tuvan (hợp đồng mục 3.1, scope results:read): báo cáo của một lượt đã nhập
 *           đúng mã email. {mã lượt} là quiz.attempt_id (gửi CRM trong gói lead/2) hoặc result_token.
 *           - view=khach: giống báo cáo khách đọc (mã khách thấy, giải thích, mức độ 6 nhóm, ngành, nghề);
 *           - view=tuvan: holland-result/3 đầy đủ (mã 3 chữ, mã thay thế, độ tin cậy, mọi ngành) + 36 câu trả lời.
 *           KHÔNG có họ tên, email, số điện thoại (chatbot lấy thông tin khách ở CRM).
 * Ai gọi:   chatbot (Python), CRM; middleware api.token:results:read.
 * Dữ liệu:  test_sessions (result đã lưu), danh mục.
 */
class ResultController extends Controller
{
    public function show(Request $request, string $token, LeadPayload $lead): JsonResponse
    {
        abort_unless(Str::isUuid($token), 404);

        $session = TestSession::query()->whereNotNull('verified_at')
            ->where(fn ($q) => $q->where('uuid', $token)->orWhere('result_token', $token))
            ->first();
        if ($session === null) {
            return response()->json(['message' => 'Không tìm thấy kết quả.'], 404);
        }

        $view = $request->query('view') === 'tuvan' ? 'tuvan' : 'khach';
        $base = [
            'schema_version' => 'holland-api-result/1',
            'view' => $view,
            'attempt_id' => $session->uuid,
            'report_code' => InternalReport::code($session),
            'completed_at' => $session->answered_at?->toIso8601String(),
        ];

        if ($view === 'tuvan') {
            return response()->json($base + [
                'holland_result' => $lead->result($session->result),
                'answers' => $lead->answers($session),
            ]);
        }

        $report = CustomerReport::make($session);

        return response()->json($base + [
            'code' => $report['code'],
            'names' => $report['names'],
            'opening' => $report['opening'],
            'groups' => $report['explain'],
            'third_letter' => $report['third'],
            'combination' => $report['combine'],
            'levels' => array_map(fn (array $row) => ['code' => $row['code'], 'name' => $row['name'], 'level' => $row['band']], $report['scale']),
            'majors' => array_map(fn (array $m) => array_intersect_key($m, array_flip(['name_vi', 'name_de', 'routes', 'label', 'why', 'occupations'])), $report['majors']),
        ]);
    }
}
