<?php

namespace App\Http\Controllers\Crm\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Làm gì:   POST /api/v1/leads — nhận khách từ các kênh theo gói lead/2 (HOP_DONG_KET_NOI mục 2.1).
 * Ai gọi:   web chính (form liên hệ, job SendContactToCrm); sau này Holland, chatbot…
 * Dữ liệu:  đọc/ghi leads, lead_activities, crm_intake_events. Không ghi SĐT, email, họ tên vào log.
 *
 * TẠM THỜI chạy trong viger_web; khi viger_crm thành app riêng sẽ chuyển sang module Intake (giai đoạn C3).
 */
class LeadIntakeController extends Controller
{
    /** Kênh trong gói => nguồn (Lead::SOURCES) */
    private const CHANNEL_SOURCES = ['website' => 'website', 'holland' => 'holland', 'chatbot' => 'chatbot'];

    public function store(Request $request): JsonResponse
    {
        $channel = $request->attributes->get('crm_channel');

        $validator = Validator::make($request->all(), [
            'schema_version' => ['required', 'in:lead/2'],
            'event_id' => ['required', 'uuid'],
            'channel' => ['required', 'string'],
            'consent.agreed' => ['required', 'accepted'],
            'consent.text' => ['required', 'string', 'max:2000'],
            'contact' => ['required', 'array'],
            'contact.full_name' => ['nullable', 'string', 'max:255'],
            'contact.phone' => ['required_without:contact.email', 'nullable', 'regex:/^\+?\d{9,15}$/'],
            'contact.email' => ['required_without:contact.phone', 'nullable', 'email', 'max:255'],
            'extra_fields' => ['nullable', 'array'],
            'extra_fields.loi_nhan' => ['nullable', 'string', 'max:2000'],
        ]);

        // 422 chỉ ghi tên trường, không lặp lại giá trị (hợp đồng mục 1)
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => array_fill_keys($validator->errors()->keys(), ['invalid']),
            ], 422);
        }

        // Token chỉ được gửi cho đúng kênh của nó
        if ($request->input('channel') !== $channel) {
            return response()->json(['message' => 'Token không thuộc kênh này.'], 403);
        }

        // Idempotent: gửi lại cùng event_id => trả lại kết quả cũ
        $eventId = $request->input('event_id');
        if ($seen = DB::table('crm_intake_events')->where('event_id', $eventId)->value('response')) {
            return response()->json(json_decode($seen, true));
        }

        return DB::transaction(function () use ($request, $channel, $eventId) {
            $contact = $request->input('contact');
            $phone = self::localPhone($contact['phone'] ?? null);
            $email = $contact['email'] ?? null;
            $message = $request->input('extra_fields.loi_nhan');
            $sourceLabel = Lead::SOURCES[self::CHANNEL_SOURCES[$channel] ?? 'other'];

            // Khớp khách: SĐT (cả dạng 0… và +84…) → email
            $lead = $phone
                ? Lead::whereIn('phone', [$phone, '+84'.substr($phone, 1)])->first()
                : null;
            $lead ??= $email ? Lead::where('email', $email)->first() : null;

            $isNew = ! $lead;

            if ($isNew) {
                // Lead::created tự ghi "CRM tiếp nhận khách hàng từ nguồn …"
                $lead = Lead::create([
                    'full_name' => $contact['full_name'] ?? 'Khách chưa rõ tên',
                    'phone' => $phone ?? 'chua-co-sdt-'.substr($eventId, 0, 8),
                    'email' => $email,
                    'source' => self::CHANNEL_SOURCES[$channel] ?? 'other',
                    'status' => 'new',
                    'note' => $message,
                ]);
            } else {
                // Không ghi đè dữ liệu cũ bằng giá trị rỗng: chỉ bổ sung trường đang trống
                $lead->fill(array_filter([
                    'email' => $lead->email ? null : $email,
                    'note' => $lead->note ? null : $message,
                ]))->save();

                $nameNote = ! empty($contact['full_name']) && $contact['full_name'] !== $lead->full_name
                    ? " (tên trên form: {$contact['full_name']})" : '';
                $lead->log("Khách quay lại, gửi thông tin qua {$sourceLabel}{$nameNote}.");
            }

            if ($message) {
                $lead->log($message, 'form');
            }

            $lead->load('assignee');
            $response = [
                'customer_code' => $lead->code(),
                'lead_code' => $lead->code(),
                'is_new' => $isNew,
                'priority' => null,   // xếp loại GREEN/YELLOW/RED: giai đoạn C2/C3
                'assigned_to' => $lead->assignee?->displayName(),
            ];

            DB::table('crm_intake_events')->insert([
                'event_id' => $eventId,
                'channel' => $channel,
                'lead_id' => $lead->id,
                'response' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json($response, $isNew ? 201 : 200);
        });
    }

    /** +84901234567 / 84901234567 / 0901234567 => 0901234567 (định dạng đang lưu trong leads) */
    private static function localPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return match (true) {
            $digits === '' => null,
            str_starts_with($digits, '84') => '0'.substr($digits, 2),
            default => $digits,
        };
    }
}
