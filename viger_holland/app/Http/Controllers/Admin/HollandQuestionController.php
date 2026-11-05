<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkQuestionsRequest;
use App\Http\Requests\Admin\HollandQuestionRequest;
use App\Models\AdminActivity;
use App\Models\Question;
use App\Models\QuestionSet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Câu Holland trong một bộ: thêm, dán nhiều câu, sửa.
 * Bật/tắt, thùng rác, sắp xếp: QuestionController (dùng chung với Phần A).
 */
class HollandQuestionController extends Controller
{
    public function create(QuestionSet $questionSet): View
    {
        return view('admin.holland-questions.form', [
            'set' => $questionSet,
            'question' => new Question([
                'is_active' => true,
                'is_required' => true,
                'riasec' => request('nhom'),
                // Câu mới theo kiểu vừa dùng (khi bấm "Lưu và thêm câu khác"), không thì theo kiểu mặc định của bộ.
                'answer_type' => request('kieu') ?: $questionSet->answer_type,
            ]),
            'activities' => collect(),
        ]);
    }

    public function store(HollandQuestionRequest $request, QuestionSet $questionSet): RedirectResponse
    {
        $question = Question::create([
            ...$request->questionAttributes($questionSet),
            'code' => Question::nextCode(Question::PART_HOLLAND),
            'part' => Question::PART_HOLLAND,
            'question_set_id' => $questionSet->id,
            'sort_order' => $request->filled('sort_order') ? (int) $request->input('sort_order') : $this->nextSortOrder($questionSet),
        ]);

        AdminActivity::record(AdminActivity::ACTION_CREATE, AdminActivity::SUBJECT_QUESTION, $question->id, 'Thêm câu '.$question->shortLabel().' vào bộ "'.$questionSet->name.'"');

        if ($request->boolean('add_another')) {
            return redirect()->route('admin.question-sets.questions.create', [$questionSet, 'nhom' => $question->riasec, 'kieu' => $question->answer_type->value])
                ->with('status', 'Đã thêm câu '.$question->code.'. Nhập câu tiếp theo.');
        }

        return redirect()->route('admin.question-sets.show', $questionSet)->with('status', 'Đã thêm câu '.$question->code.'.');
    }

    public function bulkStore(BulkQuestionsRequest $request, QuestionSet $questionSet): RedirectResponse
    {
        $rows = array_values($request->parsedRows());

        $codes = DB::transaction(function () use ($rows, $questionSet): array {
            $sort = $this->nextSortOrder($questionSet);
            $codes = [];

            foreach ($rows as $row) {
                $question = Question::create([
                    'code' => Question::nextCode(Question::PART_HOLLAND),
                    'part' => Question::PART_HOLLAND,
                    'question_set_id' => $questionSet->id,
                    'text' => $row['text'],
                    'riasec' => $row['riasec'],
                    'answer_type' => $questionSet->answer_type,
                    'is_required' => true,
                    'is_active' => true,
                    'sort_order' => $sort++,
                ]);
                $codes[] = $question->code;
            }

            return $codes;
        });

        AdminActivity::record(AdminActivity::ACTION_CREATE, AdminActivity::SUBJECT_SET, $questionSet->id, 'Dán '.count($codes).' câu vào bộ "'.$questionSet->name.'" ('.reset($codes).'–'.end($codes).')');

        return redirect()->route('admin.question-sets.show', $questionSet)
            ->with('status', 'Đã thêm '.count($codes).' câu (mã '.reset($codes).' đến '.end($codes).').');
    }

    public function edit(Question $question): View
    {
        abort_unless($question->isHolland(), 404);

        return view('admin.holland-questions.form', [
            'set' => $question->questionSet,
            'question' => $question,
            'activities' => AdminActivity::forSubject(AdminActivity::SUBJECT_QUESTION, $question->id),
        ]);
    }

    public function update(HollandQuestionRequest $request, Question $question): RedirectResponse
    {
        abort_unless($question->isHolland(), 404);

        $question->fill([
            ...$request->questionAttributes($question->questionSet),
            'sort_order' => $request->filled('sort_order') ? (int) $request->input('sort_order') : $question->sort_order,
        ])->save();

        AdminActivity::recordModelChange($question, AdminActivity::SUBJECT_QUESTION, 'Sửa câu '.$question->shortLabel());

        return redirect()->route('admin.question-sets.show', $question->question_set_id)->with('status', 'Đã lưu câu '.$question->code.'.');
    }

    private function nextSortOrder(QuestionSet $set): int
    {
        return (int) Question::withTrashed()->where('question_set_id', $set->id)->max('sort_order') + 1;
    }
}
