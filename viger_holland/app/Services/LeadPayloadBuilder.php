<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Question;
use App\Support\LeadFields;
use Illuminate\Support\Carbon;

/**
 * Dựng gói `lead/1` gửi CRM (docs/HOP_DONG_KET_NOI.md mục 3.1) từ một lượt làm bài.
 */
final class LeadPayloadBuilder
{
    public const SCHEMA_VERSION = 'lead/1';

    /**
     * @return array<string, mixed>
     */
    public function build(Attempt $attempt): array
    {
        // Trường chuẩn của lead/1: luôn có mặt, null nếu khách không trả lời.
        $fields = array_fill_keys(LeadFields::standard(), null);
        $extra = [];
        $contact = $attempt->contact ?? [];

        $questions = Question::withTrashed()->part(Question::PART_INFO)->whereIn('code', array_keys($contact))->get()->keyBy('code');

        foreach ($contact as $code => $value) {
            $question = $questions[$code] ?? null;

            if ($question === null || $question->lead_field === 'consent') {
                continue;
            }

            if (filled($question->lead_field)) {
                $fields[$question->lead_field] = $this->cast($question->lead_field, $value);
            } elseif (filled($question->extra_field_key)) {
                $extra[$question->extra_field_key] = $value;
            }
        }

        // Năm sinh tự điền từ ngày sinh (A19 → extra_fields.birth_date)
        if ($fields['birth_year'] === null && filled($extra['birth_date'] ?? null)) {
            $fields['birth_year'] = (int) Carbon::parse($extra['birth_date'])->year;
        }

        $fields['appointment_at'] = $attempt->appointment_at?->toIso8601String();

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'source' => 'holland',
            'external_ref' => 'HL-'.$attempt->uuid,
            'consent' => true,
            ...$fields,
            'extra_fields' => (object) $extra,
            'holland_result' => collect($attempt->result)->except('ranking')->all(),
            'quiz' => [
                'attempt_id' => $attempt->uuid,
                'completed_at' => $attempt->completed_at->toIso8601String(),
                'question_set_version' => $attempt->questionSet?->version(),
                'question_set' => $attempt->questionSet?->code,
                'answers' => $attempt->answers,
                'view_url' => route('admin.attempts.show', $attempt),
            ],
        ];
    }

    private function cast(string $field, mixed $value): mixed
    {
        return match ($field) {
            'birth_year' => (int) $value,
            'gpa' => (float) $value,
            default => $value,
        };
    }
}
