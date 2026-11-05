<?php

namespace App\Actions;

use App\Contracts\MajorCatalog;
use App\Contracts\ScoringEngine;
use App\Mail\TestResultMail;
use App\Models\Attempt;
use App\Models\OutboxMessage;
use App\Models\Question;
use App\Services\LeadPayloadBuilder;
use App\Services\TestForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Nộp bài: chấm điểm → lưu lượt làm bài → đưa gói lead vào hàng đợi CRM → gửi email kết quả (sau khi trả trang).
 */
final class SubmitTest
{
    public function __construct(
        private readonly ScoringEngine $engine,
        private readonly MajorCatalog $catalog,
        private readonly LeadPayloadBuilder $leadBuilder,
    ) {}

    /**
     * @param  array<string, string>  $hollandAnswers  [mã câu B => giá trị]
     * @param  array<string, mixed>  $info  [mã câu A => câu trả lời]
     * @param  array<string, string>  $utm
     */
    public function handle(TestForm $form, array $hollandAnswers, array $info, array $utm = [], string $source = 'web'): Attempt
    {
        $quizAnswers = $form->hollandQuestions->map(fn (Question $question) => [
            'code' => $question->code,
            'group' => $question->riasec,
            'text' => $question->text,
            'value' => self::number($hollandAnswers[$question->code]),
            'max' => self::number($question->maxPoints($form->set)),
        ])->values()->all();

        $result = $this->engine->score($quizAnswers, $this->catalog->all());

        $attempt = DB::transaction(function () use ($form, $quizAnswers, $info, $utm, $source, $result): Attempt {
            $attempt = Attempt::create([
                'uuid' => (string) Str::uuid(),
                'result_token' => (string) Str::uuid(),
                'question_set_id' => $form->set->id,
                'source' => $source,
                'engine' => $result->engine,
                'engine_version' => $result->engineVersion,
                'holland_code' => $result->code,
                'contact' => $info,
                'answers' => $quizAnswers,
                'result' => $result->toArray(withRanking: true),
                'utm' => $utm ?: null,
                'crm_sync_status' => Attempt::SYNC_PENDING,
                'completed_at' => now(),
            ]);

            $this->queueLead($attempt);

            return $attempt;
        });

        $emailCode = $form->infoQuestions->firstWhere('lead_field', 'email')?->code;
        $this->sendResultEmail($attempt, $emailCode ? ($info[$emailCode] ?? null) : null);

        return $attempt;
    }

    private static function number(float|int|string $value): int|float
    {
        $number = (float) $value;

        return floor($number) === $number ? (int) $number : $number;
    }

    public function queueLead(Attempt $attempt): OutboxMessage
    {
        return $attempt->outboxMessages()->create([
            'type' => OutboxMessage::TYPE_LEAD,
            'payload' => $this->leadBuilder->build($attempt),
            'status' => OutboxMessage::STATUS_PENDING,
            'next_attempt_at' => now(),
        ]);
    }

    /**
     * Gửi sau khi đã trả trang cho khách (khách không phải chờ). Lỗi gửi mail không làm hỏng việc xem kết quả.
     * Không ghi email/SĐT vào log.
     */
    private function sendResultEmail(Attempt $attempt, ?string $email): void
    {
        if (blank($email)) {
            return;
        }

        defer(function () use ($attempt, $email): void {
            try {
                Mail::to($email)->send(new TestResultMail($attempt));
            } catch (Throwable $exception) {
                Log::warning('Không gửi được email kết quả', ['attempt' => $attempt->uuid, 'error' => class_basename($exception)]);
            }
        });
    }
}
