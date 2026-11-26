<?php

namespace App\Http\Controllers\Crm\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Làm gì:   POST /api/v1/events — nhận hành vi bổ sung theo gói events/1 (HOP_DONG_KET_NOI mục 2.2), sau khi kênh
 *           đã có customer_code (VD khách quay lại xem báo cáo Holland, bấm Gọi / Zalo). Ghi vào lịch sử khách.
 * Ai gọi:   viger_holland (CrmSync / SendToCrm), sau này web chính, chatbot.
 * Dữ liệu:  ghi lead_activities (type tracking), crm_intake_events (idempotent theo event_id).
 *
 * Mã khách không tồn tại => 422 (kênh đánh dấu lỗi, không gửi lại vô ích).
 */
class EventIntakeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $channel = $request->attributes->get('crm_channel');

        $validator = Validator::make($request->all(), [
            'schema_version' => ['required', 'in:events/1'],
            'event_id' => ['required', 'uuid'],
            'channel' => ['required', 'string'],
            'customer_code' => ['required', 'string', 'max:40'],
            'events' => ['required', 'array', 'min:1', 'max:200'],
            'events.*.type' => ['required', 'string', 'max:50'],
            'events.*.at' => ['nullable', 'date'],
            'events.*.meta' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ.', 'errors' => array_fill_keys($validator->errors()->keys(), ['invalid'])], 422);
        }

        if ($request->input('channel') !== $channel) {
            return response()->json(['message' => 'Token không thuộc kênh này.'], 403);
        }

        $eventId = $request->input('event_id');
        if ($seen = DB::table('crm_intake_events')->where('event_id', $eventId)->value('response')) {
            return response()->json(json_decode($seen, true));
        }

        $lead = Lead::findByCode($request->input('customer_code'));
        if (! $lead) {
            return response()->json(['message' => 'Không tìm thấy khách hàng.', 'errors' => ['customer_code' => ['invalid']]], 422);
        }

        return DB::transaction(function () use ($request, $channel, $eventId, $lead) {
            $response = [
                'customer_code' => $lead->code(),
                'accepted' => LeadIntakeController::logEvents($lead, $request->input('events')),
            ];

            DB::table('crm_intake_events')->insert([
                'event_id' => $eventId,
                'channel' => $channel,
                'lead_id' => $lead->id,
                'response' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json($response);
        });
    }
}
