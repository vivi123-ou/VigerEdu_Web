<?php

namespace App\Services;

use App\Contracts\MajorCatalog;
use App\Contracts\ScoringEngine;
use App\Mail\TestResultMail;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Nút "Xem trước": dựng trang khách với giá trị đang gõ (chưa lưu) và kết quả mẫu (học sinh mẫu, không lưu database).
 */
final class PagePreview
{
    /** % 6 nhóm của học sinh mẫu HS01 (test_cases.json). */
    private const SAMPLE_PERCENT = ['R' => 0.3333, 'I' => 0.6667, 'A' => 0.8889, 'S' => 0.5556, 'E' => 0.1111, 'C' => 0.2222];

    public function __construct(
        private readonly ScoringEngine $engine,
        private readonly MajorCatalog $catalog,
        private readonly ResultPresenter $presenter,
    ) {}

    /**
     * @param  array<string, string>  $values
     */
    public function render(string $target, array $values): string
    {
        Setting::preview($values);

        try {
            return match ($target) {
                'result' => view('public.result', ['attempt' => $attempt = $this->sampleAttempt(), 'result' => $this->presenter->present($attempt), 'isPreview' => true])->render(),
                'email' => (new TestResultMail($this->sampleAttempt()))->render(),
                'embed' => view('public.embed', ['isPreview' => true])->render(),
                default => view('public.home', ['isPreview' => true])->render(),
            };
        } finally {
            // Giá trị xem trước chỉ dùng cho lần dựng trang này.
            Setting::preview([]);
        }
    }

    /**
     * Lượt làm bài mẫu (không lưu): kết quả học sinh mẫu, tên "An".
     */
    public function sampleAttempt(): Attempt
    {
        $result = $this->engine->scorePercent(self::SAMPLE_PERCENT, $this->catalog->all());

        $nameCode = Question::query()->where('lead_field', 'student_name')->value('code');

        return new Attempt([
            'uuid' => (string) Str::uuid(),
            'result_token' => (string) Str::uuid(),
            'holland_code' => $result->code,
            'contact' => $nameCode ? [$nameCode => 'Nguyễn Văn An'] : [],
            'answers' => [],
            'result' => $result->toArray(withRanking: true),
            'completed_at' => now(),
        ]);
    }
}
