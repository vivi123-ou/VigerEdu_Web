<?php

namespace App\Services;

use App\Contracts\MajorCatalog;
use App\Contracts\ScoringEngine;
use App\Mail\TestResultMail;
use App\Models\Attempt;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Nút "Xem trước": dựng trang khách với giá trị đang gõ (chưa lưu) và một kết quả minh họa (không lưu database).
 */
final class PagePreview
{
    /** % 6 nhóm của hồ sơ minh họa (chỉ để hiện trang kết quả khi xem trước). */
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
     * Lượt làm bài minh họa (không lưu) cho "Xem trước" / "Sửa trên trang". Tên khách hiện là "[Tên khách]".
     */
    public function sampleAttempt(): Attempt
    {
        $result = $this->engine->scorePercent(self::SAMPLE_PERCENT, $this->catalog->all());

        $attempt = new Attempt([
            'uuid' => (string) Str::uuid(),
            'result_token' => (string) Str::uuid(),
            'holland_code' => $result->code,
            'contact' => [],
            'answers' => [],
            'result' => $result->toArray(withRanking: true),
            'completed_at' => now(),
        ]);
        $attempt->setAttribute('preview_name', '[Tên khách]');

        return $attempt;
    }
}
