<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\QuestionSet;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lượt làm bài. Không có danh sách khách (SĐT, email, chăm sóc khách nằm ở CRM).
 */
class AttemptController extends Controller
{
    public function index(Request $request): View
    {
        $days = ['hom-nay' => 0, '7-ngay' => 7, '30-ngay' => 30][(string) $request->query('thoi_gian')] ?? null;

        $attempts = Attempt::query()
            ->with('questionSet')
            ->when($request->filled('ma'), fn ($query) => $query->where('holland_code', strtoupper(trim((string) $request->query('ma')))))
            ->when($request->filled('bo'), fn ($query) => $query->where('question_set_id', (int) $request->query('bo')))
            ->when($days !== null, fn ($query) => $query->where('completed_at', '>=', now()->subDays($days)->startOfDay()))
            ->latest('completed_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.attempts.index', [
            'attempts' => $attempts,
            'sets' => QuestionSet::query()->orderBy('name')->pluck('name', 'id'),
            'isFiltered' => $request->hasAny(['ma', 'bo', 'thoi_gian']) && collect($request->only(['ma', 'bo', 'thoi_gian']))->filter()->isNotEmpty(),
        ]);
    }

    public function show(Request $request, Attempt $attempt): View
    {
        $type = $request->query('loai');
        $field = $request->query('linh_vuc');
        $ranking = collect($attempt->result['ranking'] ?? []);
        $fields = $ranking->pluck('field')->unique()->sort()->values();

        $types = collect($attempt->result['top_university'] ?? [])->pluck('major_code')
            ->merge(collect($attempt->result['top_vocational'] ?? [])->pluck('major_code'));

        $filtered = $ranking
            ->when($type === 'dai-hoc', fn ($rows) => $rows->filter(fn ($row) => str_starts_with($row['major_code'], 'N')))
            ->when($type === 'hoc-nghe', fn ($rows) => $rows->filter(fn ($row) => str_starts_with($row['major_code'], 'H')))
            ->when(filled($field), fn ($rows) => $rows->where('field', $field))
            ->values();

        // Câu "thông tin riêng tư" (họ tên, SĐT, email…) không hiện ở đây, xem ở CRM.
        $infoQuestions = Question::withTrashed()->part(Question::PART_INFO)->ordered()->get()
            ->reject(fn (Question $question) => $question->isPrivate());

        return view('admin.attempts.show', [
            'attempt' => $attempt,
            'ranking' => $filtered,
            'fields' => $fields,
            'topCodes' => $types,
            'infoRows' => $infoQuestions
                ->filter(fn (Question $question) => filled($attempt->contact[$question->code] ?? null))
                ->map(fn (Question $question) => ['label' => $question->text, 'value' => $this->displayValue($question, $attempt->contact[$question->code])]),
        ]);
    }

    private function displayValue(Question $question, mixed $value): string
    {
        $labels = collect($question->options ?? [])->mapWithKeys(fn (array $option) => [(string) $option['value'] => $option['label']]);

        return collect((array) $value)
            ->map(fn ($item) => $labels[(string) $item] ?? (string) $item)
            ->implode(', ');
    }
}
