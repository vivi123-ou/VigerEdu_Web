<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lượt làm bài. Không có danh sách khách (SĐT, email, chăm sóc khách nằm ở CRM).
 */
class AttemptController extends Controller
{
    /** Trường liên hệ không hiện trong admin Holland (xem ở CRM). */
    private const PRIVATE_FIELDS = ['student_name', 'phone', 'email', 'parent_name', 'parent_phone', 'consent'];

    private const PRIVATE_EXTRA = ['parent_email', 'birth_date'];

    public function index(Request $request): View
    {
        $attempts = Attempt::query()
            ->with('questionSet')
            ->when($request->filled('ma'), fn ($query) => $query->where('holland_code', strtoupper((string) $request->query('ma'))))
            ->latest('completed_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.attempts.index', ['attempts' => $attempts]);
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

        $infoQuestions = Question::withTrashed()->part(Question::PART_INFO)->ordered()->get()
            ->reject(fn (Question $question) => in_array($question->lead_field, self::PRIVATE_FIELDS, true)
                || in_array($question->extra_field_key, self::PRIVATE_EXTRA, true));

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
        $label = collect($question->options ?? [])->firstWhere('value', (string) $value)['label'] ?? null;

        return (string) ($label ?? (is_array($value) ? implode(', ', $value) : $value));
    }
}
