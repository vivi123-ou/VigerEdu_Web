<?php

namespace App\Modules\CrmSync\Services;

use App\Modules\Assessment\Models\SessionQuestion;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Respondent\Filament\Pages\InternalReportPage;
use App\Modules\Respondent\Models\Consent;
use App\Modules\Respondent\Models\FieldValue;
use App\Modules\Respondent\Support\InternalReport;
use App\Modules\Tracking\Models\TrackingEvent;
use Illuminate\Support\Arr;

/**
 * Làm gì:   Dựng gói lead/2 (docs/HOP_DONG_KET_NOI.md mục 2.1) cho một lượt làm bài đã nhập đúng mã email:
 *           thông tin khách theo crm_key của ô form (config/crm.php), consent ĐÚNG sự thật (chỉ agreed = true khi khách
 *           thật sự thấy và bấm qua câu đồng ý), kết quả holland-result/3, 36 câu trả lời, hành vi, nguồn UTM.
 *           Bỏ mọi ô rỗng / null (hợp đồng: chỉ gửi trường có giá trị). SĐT gửi dạng +84….
 * Ai gọi:   CrmSync::queueLead(), lệnh crm:payload (in mẫu đã che).
 * Dữ liệu:  test_sessions, respondents, field_values + form_fields, consents, session_questions, responses, tracking_events.
 */
class LeadPayload
{
    /** Khóa nội bộ: id các hành vi đã gói vào lead (bỏ đi trước khi gửi, dùng để đánh dấu đã gửi). */
    public const TRACKING_IDS = '__tracking_ids';

    /** Quan hệ 2 nhóm đầu trên lục giác → mã gửi CRM (hợp đồng mục 3.2). */
    private const RELATIONS = ['Liền kề' => 'lien_ke', 'Cách một' => 'cach_mot', 'Đối kháng' => 'doi_khang'];

    /**
     * @return array<string, mixed>
     */
    public function build(TestSession $session, string $eventId): array
    {
        $session->loadMissing('respondent');
        $respondent = $session->respondent;
        $result = $session->result;
        [$fields, $extra] = $this->fields($session);
        $events = TrackingEvent::query()->where('test_session_id', $session->id)->orderBy('occurred_at')->orderBy('id')->get();
        $consent = Consent::query()->where('test_session_id', $session->id)->latest('id')->first();

        $payload = [
            'schema_version' => 'lead/2',
            'event_id' => $eventId,
            'channel' => 'holland',
            'external_ref' => InternalReport::code($session),
            'customer_code' => $respondent?->crm_customer_code,
            'consent' => $consent
                ? ['agreed' => true, 'version' => $consent->version, 'agreed_at' => $consent->agreed_at->toIso8601String(), 'text' => $consent->text_snapshot]
                : ['agreed' => false],
            'contact' => array_merge($fields['contact'] ?? [], [
                'phone_verified' => false,
                'email_verified' => $respondent?->email_verified_at !== null,
            ]),
            'guardian' => $fields['guardian'] ?? null,
            'profile' => $fields['profile'] ?? null,
            'extra_fields' => $extra ?: null,
            'holland_result' => $this->result($result),
            'quiz' => [
                'attempt_id' => $session->uuid,
                'completed_at' => $session->answered_at?->toIso8601String(),
                'form_version' => $session->test_config_version_id ? 'cfg-'.$session->test_config_version_id : null,
                'answers' => $this->answers($session),
                'view_url' => InternalReportPage::getUrl(['ma' => $session->uuid], panel: 'admin'),
            ],
            'events' => $events->map(fn (TrackingEvent $e) => array_filter([
                'type' => $e->type, 'at' => $e->occurred_at->toIso8601String(), 'meta' => $e->meta ?: null,
            ]))->all(),
            'utm' => collect($session->utm ?? [])->mapWithKeys(fn ($value, $key) => [str_replace('utm_', '', (string) $key) => $value])->all(),
        ];

        return self::withoutEmpty($payload) + [self::TRACKING_IDS => $events->pluck('id')->all()];
    }

    /**
     * Ô form có giá trị và được gửi CRM, xếp vào đúng vị trí theo config/crm.php; còn lại vào extra_fields.
     *
     * @return array{0: array<string, array<string, mixed>>, 1: array<string, string>}
     */
    private function fields(TestSession $session): array
    {
        $paths = config('crm.paths', []);
        $grouped = [];
        $extra = [];

        $values = FieldValue::query()->where('test_session_id', $session->id)->with('formField')->get();
        foreach ($values as $value) {
            $field = $value->formField;
            if ($field !== null && ! $field->to_crm) {
                continue;
            }
            $raw = trim((string) $value->value);
            if ($raw === '') {
                continue;
            }
            $key = $field?->crm_key;
            if ($key !== null && isset($paths[$key])) {
                Arr::set($grouped, $paths[$key], $this->format($key, $raw));
                if ($key === 'birth_date' && ! Arr::has($grouped, 'contact.birth_year')) {
                    Arr::set($grouped, 'contact.birth_year', (int) substr($raw, 0, 4));
                }
            } else {
                $extra[$value->key] = $raw;
            }
        }

        return [$grouped, $extra];
    }

    private function format(string $crmKey, string $value): string|int
    {
        return match ($crmKey) {
            'phone', 'parent_phone' => self::e164($value),
            'birth_year' => (int) $value,
            'birth_date' => substr($value, 0, 10),
            'email' => mb_strtolower($value),
            default => $value,
        };
    }

    /**
     * 0912345678 → +84912345678 (hợp đồng: chuẩn hóa ở bên gửi).
     */
    public static function e164(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return match (true) {
            str_starts_with($digits, '0') => '+84'.substr($digits, 1),
            str_starts_with($digits, '84') => '+'.$digits,
            default => $digits,
        };
    }

    /**
     * Kết quả theo holland-result/3 (hợp đồng mục 3.2). Không có phần trăm.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function result(array $result): array
    {
        $shown = array_column($result['customer']['majors'] ?? [], 'id');

        return [
            'schema_version' => 'holland-result/3',
            'engine_version' => 'average_v3',
            'scores' => collect($result['groups'])->map(fn (array $g) => round($g['mean'], 2))->all(),
            'item_counts' => collect($result['groups'])->map(fn (array $g) => $g['answered'])->all(),
            'ranking' => $result['order'],
            'code_full' => $result['code3'],
            'code_public' => $result['customer']['code'],
            'alternates' => $result['alternates'] ?? [],
            'relation' => self::RELATIONS[$result['relations']['1-2']['relation'] ?? ''] ?? null,
            'consistency' => $result['consistency'] ?? null,
            'reliability' => $result['quality']['reliability'] ?? null,
            'flags' => collect($result['quality']['flags'] ?? [])->pluck('code')->all(),
            'majors' => collect($result['majors'] ?? [])->map(fn (array $m) => [
                'id' => $m['id'], 'name_vi' => $m['name_vi'], 'name_de' => $m['name_de'] ?? null,
                'c_index' => $m['c'] ?? null, 'label' => $m['label'],
                'shown_to_customer' => in_array($m['id'], $shown, true),
            ])->all(),
        ];
    }

    /**
     * @return list<array{code: string|null, group: string, value: int|null, ms: int|null}>
     */
    private function answers(TestSession $session): array
    {
        $responses = $session->responses()->get()->keyBy('question_id');

        return $session->sessionQuestions()->with('question')->orderBy('display_order')->get()
            ->map(fn (SessionQuestion $item) => [
                'code' => $item->question?->code,
                'group' => $item->group,
                'value' => $responses[$item->question_id]?->value,
                'ms' => $responses[$item->question_id]?->ms,
            ])->all();
    }

    /**
     * Bỏ khóa có giá trị null, chuỗi rỗng, mảng rỗng (giữ false, 0).
     *
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function withoutEmpty(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::withoutEmpty($value);
                $data[$key] = $value;
            }
            if ($value === null || $value === '' || $value === []) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Gói đem gửi thật (bỏ khóa nội bộ).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function forWire(array $payload): array
    {
        unset($payload[self::TRACKING_IDS]);

        return $payload;
    }

    /**
     * Bản che để in ra cho người làm CRM xem (lệnh crm:payload): họ tên, email, SĐT được che.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function masked(array $payload): array
    {
        $payload = self::forWire($payload);
        if (Arr::has($payload, 'contact.birth_date')) {
            Arr::set($payload, 'contact.birth_date', substr((string) Arr::get($payload, 'contact.birth_date'), 0, 4).'-xx-xx');
        }
        foreach (['contact.full_name', 'guardian.full_name'] as $path) {
            if (Arr::has($payload, $path)) {
                $name = (string) Arr::get($payload, $path);
                Arr::set($payload, $path, mb_substr($name, 0, 1).str_repeat('•', max(3, mb_strlen($name) - 1)));
            }
        }
        foreach (['contact.phone', 'guardian.phone'] as $path) {
            if (Arr::has($payload, $path)) {
                Arr::set($payload, $path, self::maskPhone((string) Arr::get($payload, $path)));
            }
        }
        if (Arr::has($payload, 'contact.email')) {
            $email = (string) Arr::get($payload, 'contact.email');
            Arr::set($payload, 'contact.email', mb_substr($email, 0, 1).'•••@'.(explode('@', $email)[1] ?? ''));
        }
        if (isset($payload['consent']['text'])) {
            $payload['consent']['text'] = '…';
        }

        return $payload;
    }

    /**
     * +84912345678 / 0912345678 → 09xx xxx 678 (CLAUDE.md: cần hiện thì che như vậy).
     */
    public static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        $local = str_starts_with($digits, '84') ? '0'.substr($digits, 2) : $digits;

        return strlen($local) >= 6 ? substr($local, 0, 2).'xx xxx '.substr($local, -3) : 'xxx';
    }
}
