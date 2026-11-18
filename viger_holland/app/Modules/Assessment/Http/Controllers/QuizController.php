<?php

namespace App\Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assessment\Http\Requests\SubmitAnswersRequest;
use App\Modules\Assessment\Models\RiasecPair;
use App\Modules\Assessment\Models\RiasecType;
use App\Modules\Assessment\Models\SessionQuestion;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Services\AssessmentService;
use App\Modules\Assessment\Support\CurrentTest;
use App\Modules\Tracking\Services\Tracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Làm gì:   Luồng khách H3: bấm "Bắt đầu" → làm bài từng trang (mặc định 6 câu, chấm sao) → kết quả sơ lược.
 *           Lượt làm bài gắn với phiên của trình duyệt (không có mã trên địa chỉ trang, không chia sẻ được);
 *           câu trả lời lưu sau mỗi trang nên tải lại trang không mất bài. Máy làm lại được nhận ra bằng cookie (không dùng SĐT)
 *           để bốc câu khác lần trước.
 * Ai gọi:   routes/web.php (test.start, test.show, test.submit, test.result).
 * Dữ liệu:  test_sessions, session_questions, responses (qua AssessmentService); không có thông tin cá nhân.
 */
class QuizController extends Controller
{
    private const DEVICE_COOKIE = 'holland_device';

    public function __construct(private readonly AssessmentService $assessment, private readonly Tracker $tracker) {}

    public function start(Request $request): RedirectResponse
    {
        $current = $this->currentSession($request);
        if ($current?->status === 'in_progress') {
            return redirect()->route('test.show');
        }

        $device = (string) $request->cookie(self::DEVICE_COOKIE);
        if (! Str::isUuid($device)) {
            $device = (string) Str::uuid();
        }
        Cookie::queue(self::DEVICE_COOKIE, $device, 60 * 24 * 365);

        $utm = collect($request->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term']))
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->map(fn (string $value) => Str::limit(strip_tags($value), 100, ''))
            ->all();

        $session = $this->assessment->start(hash('sha256', $device), $utm ?: null);
        CurrentTest::remember($request, $session);
        $this->tracker->record($session, 'quiz_started', array_filter(['source' => $utm['utm_source'] ?? null]));

        return redirect()->route('test.show');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $session = $this->currentSession($request);
        if ($session === null) {
            return redirect()->route('home');
        }
        if ($session->status !== 'in_progress') {
            return redirect()->route('test.result');
        }

        $pages = $this->pages($session);
        $answers = $session->responses()->pluck('value', 'question_id')->map(fn ($v) => (int) $v)->all();
        $firstOpen = $pages->search(fn (Collection $page) => $page->contains(fn ($item) => ! isset($answers[$item->question_id])));
        $firstOpen = $firstOpen === false ? $pages->count() - 1 : $firstOpen;
        // Chỉ được quay lại trang trước, không nhảy tới trang chưa làm.
        $index = min(max((int) $request->query('trang', $firstOpen + 1) - 1, 0), $firstOpen);
        $settings = $this->assessment->settingsFor($session);

        return view('public.quiz', [
            'items' => $pages[$index],
            'page' => $index + 1,
            'pageCount' => $pages->count(),
            'startNumber' => $index * (int) $settings['items_per_page'] + 1,
            'answers' => $answers,
            'total' => $pages->flatten()->count(),
            'progress' => (int) round(count($answers) / max(1, $pages->flatten()->count()) * 100),
            'labels' => array_values($settings['likert_labels']),
        ]);
    }

    public function submit(SubmitAnswersRequest $request): RedirectResponse
    {
        $session = $this->currentSession($request);
        if ($session === null) {
            return redirect()->route('home');
        }
        if ($session->status !== 'in_progress') {
            return redirect()->route('test.result');
        }

        $pages = $this->pages($session);
        $index = min((int) $request->validated('page') - 1, $pages->count() - 1);
        $onPage = $pages[$index]->pluck('question_id')->all();
        $given = collect($request->validated('answers') ?? [])->only($onPage);
        $times = $request->validated('ms') ?? [];
        $goingBack = $request->validated('direction') === 'back';

        // Lưu ngay các câu đã chọn (kể cả khi còn thiếu) để khách không mất phần đã làm.
        $this->assessment->saveAnswers($session, $given->map(fn ($value, $id) => [
            'value' => (int) $value,
            'ms' => isset($times[$id]) ? (int) $times[$id] : null,
        ])->all());

        $answered = $session->responses()->whereIn('question_id', $onPage)->count();
        if (! $goingBack && $answered < count($onPage)) {
            throw ValidationException::withMessages(['answers' => setting('quiz_missing_alert', 'Bạn còn câu chưa chọn.')])
                ->redirectTo(route('test.show', ['trang' => $index + 1]));
        }

        if ($goingBack) {
            return redirect()->route('test.show', ['trang' => max(1, $index)]);
        }

        $this->tracker->record($session, 'quiz_page_completed', ['page' => $index + 1]);

        if (($done = $this->assessment->finishIfComplete($session)) !== null) {
            $this->tracker->record($done, 'quiz_finished', ['duration_s' => (int) $done->started_at?->diffInSeconds($done->answered_at)]);

            return redirect()->route('test.result');
        }

        return redirect()->route('test.show', ['trang' => $index + 2]);
    }

    public function result(Request $request): View|RedirectResponse
    {
        $session = $this->currentSession($request);
        if ($session === null) {
            return redirect()->route('home');
        }
        if ($session->status === 'in_progress') {
            return redirect()->route('test.show');
        }

        $customer = $session->result['customer'];
        $letters = str_split($customer['code']);
        $names = RiasecType::query()->whereIn('code', $letters)->pluck('name', 'code');
        $pair = RiasecPair::query()->where('pair', RiasecPair::keyFor($letters[0], $letters[1]))->first();

        return view('public.result', [
            'code' => $customer['code'],
            'names' => collect($letters)->map(fn ($l) => $names[$l] ?? $l)->all(),
            'interpretation' => $pair?->description,
            'majors' => $customer['majors'],
            'visible' => (int) $this->assessment->settingsFor($session)['preview_majors'],
        ]);
    }

    private function currentSession(Request $request): ?TestSession
    {
        return CurrentTest::get($request);
    }

    /**
     * Đề của lượt chia thành các trang theo "Mỗi trang hiện mấy câu" (của cài đặt lúc phát đề).
     *
     * @return Collection<int, Collection<int, SessionQuestion>>
     */
    private function pages(TestSession $session): Collection
    {
        $perPage = max(1, (int) $this->assessment->settingsFor($session)['items_per_page']);

        return $session->sessionQuestions()->orderBy('display_order')->get()->chunk($perPage)->values()->map(fn (Collection $c) => $c->values());
    }
}
