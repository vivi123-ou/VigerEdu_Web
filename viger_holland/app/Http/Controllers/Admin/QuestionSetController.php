<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnswerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuestionSetRequest;
use App\Models\AdminActivity;
use App\Models\Question;
use App\Models\QuestionSet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Bộ câu hỏi Phần B: danh sách, thêm, sửa, nhân bản, thùng rác.
 */
class QuestionSetController extends Controller
{
    public function index(): View
    {
        return view('admin.question-sets.index', [
            'sets' => QuestionSet::query()
                ->withCount(['questions', 'questions as active_questions_count' => fn ($query) => $query->where('is_active', true)])
                ->orderByDesc('is_default')->orderBy('sort_order')->orderBy('name')
                ->get(),
            'trashed' => QuestionSet::onlyTrashed()->latest('deleted_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.question-sets.form', ['set' => new QuestionSet(['answer_type' => AnswerType::YesNo, 'per_page' => 9, 'is_active' => false])]);
    }

    public function store(QuestionSetRequest $request): RedirectResponse
    {
        $type = AnswerType::from($request->input('answer_type'));

        $set = QuestionSet::create([
            ...$request->safe()->only(['name', 'code', 'description', 'per_page', 'is_active', 'display', 'shuffle_groups']),
            'answer_type' => $type,
            'scale' => $type->defaultScale(),
            'is_default' => false,
            'sort_order' => (int) QuestionSet::max('sort_order') + 1,
        ]);

        if ($request->boolean('is_default')) {
            $set->makeDefault();
        }

        AdminActivity::record(AdminActivity::ACTION_CREATE, AdminActivity::SUBJECT_SET, $set->id, 'Thêm bộ câu "'.$set->name.'"');

        return redirect()->route('admin.question-sets.show', $set)
            ->with('status', 'Đã tạo bộ câu "'.$set->name.'". Bây giờ thêm câu hỏi: bấm "+ Thêm câu" hoặc "Dán nhiều câu".');
    }

    public function show(QuestionSet $questionSet): View
    {
        return view('admin.question-sets.show', [
            'set' => $questionSet,
            'questions' => $questionSet->questions()->orderBy('code')->get()->sortBy('sort_order')->values(),
            'trashed' => Question::onlyTrashed()->where('question_set_id', $questionSet->id)->latest('deleted_at')->get(),
            'groupCounts' => $questionSet->questions()->where('is_active', true)->get()->flatMap(fn (Question $question) => $question->scoredGroups())->countBy(),
            'activities' => AdminActivity::forSubject(AdminActivity::SUBJECT_SET, $questionSet->id, 5),
        ]);
    }

    public function edit(QuestionSet $questionSet): View
    {
        return view('admin.question-sets.form', ['set' => $questionSet]);
    }

    public function update(QuestionSetRequest $request, QuestionSet $questionSet): RedirectResponse
    {
        $type = AnswerType::from($request->input('answer_type'));
        $typeChanged = $questionSet->answer_type !== $type;

        if ($questionSet->is_default && ! $request->boolean('is_active')) {
            return back()->withInput()->withErrors(['is_active' => 'Không tắt được bộ mặc định. Hãy chọn bộ khác làm mặc định trước.']);
        }

        $oldType = $questionSet->answer_type;

        DB::transaction(function () use ($request, $questionSet, $type, $typeChanged, $oldType): void {
            $questionSet->fill($request->safe()->only(['name', 'code', 'description', 'per_page', 'is_active', 'display', 'shuffle_groups']));
            $questionSet->answer_type = $type;
            $questionSet->scale = $typeChanged ? $type->defaultScale() : $this->scaleWithLabels($questionSet, (array) $request->input('scale_labels', []));
            $questionSet->save();

            // Đổi kiểu mặc định: các câu đang dùng thang chung của bộ đổi theo, điểm tối đa theo thang mới.
            // Câu có kiểu / thang riêng (chọn 1, chọn nhiều, thang tự đặt…) giữ nguyên.
            if ($typeChanged) {
                $questionSet->questions()->whereNull('options')->where('answer_type', $oldType->value)
                    ->update(['max_score' => null, 'answer_type' => $type->value]);
            }

            AdminActivity::recordModelChange($questionSet, AdminActivity::SUBJECT_SET, 'Sửa bộ câu "'.$questionSet->name.'"');

            if ($request->boolean('is_default') && ! $questionSet->is_default) {
                $questionSet->makeDefault();
            }
        });

        $message = $typeChanged
            ? 'Đã lưu. Kiểu trả lời đã đổi nên nhãn các mức được đặt lại như mặc định – bạn có thể sửa nhãn ngay bên dưới.'
            : 'Đã lưu bộ câu "'.$questionSet->name.'".';

        return redirect()->route($typeChanged ? 'admin.question-sets.edit' : 'admin.question-sets.show', $questionSet)->with('status', $message);
    }

    /**
     * Nhân bản: bộ mới (đang tắt) với bản sao mọi câu, mã câu mới.
     */
    public function duplicate(QuestionSet $questionSet): RedirectResponse
    {
        $copy = DB::transaction(function () use ($questionSet): QuestionSet {
            $code = $questionSet->code.'-ban-sao';
            $suffix = 2;
            while (QuestionSet::withTrashed()->where('code', $code)->exists()) {
                $code = $questionSet->code.'-ban-sao-'.$suffix++;
            }

            $copy = $questionSet->replicate(['is_default']);
            $copy->fill(['code' => $code, 'name' => $questionSet->name.' (bản sao)', 'is_active' => false, 'is_default' => false, 'sort_order' => (int) QuestionSet::max('sort_order') + 1]);
            $copy->save();

            foreach ($questionSet->questions()->orderBy('code')->get()->sortBy('sort_order')->values() as $question) {
                $clone = $question->replicate();
                $clone->code = Question::nextCode(Question::PART_HOLLAND);
                $clone->question_set_id = $copy->id;
                $clone->save();
            }

            return $copy;
        });

        AdminActivity::record(AdminActivity::ACTION_CREATE, AdminActivity::SUBJECT_SET, $copy->id, 'Nhân bản bộ câu "'.$questionSet->name.'" thành "'.$copy->name.'"');

        return redirect()->route('admin.question-sets.show', $copy)
            ->with('status', 'Đã nhân bản. Bộ mới đang TẮT – sửa xong thì bấm "Sửa thông tin bộ" để bật.');
    }

    /**
     * Cột "Hiển thị" trên danh sách: bật / tắt bộ câu (khách mở được link hay không).
     */
    public function toggle(QuestionSet $questionSet): RedirectResponse
    {
        if ($questionSet->is_default && $questionSet->is_active) {
            return back()->withErrors(['set' => 'Không tắt được bộ mặc định. Hãy chọn bộ khác làm mặc định trước.']);
        }

        $questionSet->update(['is_active' => ! $questionSet->is_active]);
        AdminActivity::recordModelChange($questionSet, AdminActivity::SUBJECT_SET, ($questionSet->is_active ? 'Bật' : 'Tắt').' bộ câu "'.$questionSet->name.'"');

        return back()->with('status', ($questionSet->is_active ? 'Đã bật' : 'Đã tắt').' bộ "'.$questionSet->name.'".');
    }

    public function destroy(QuestionSet $questionSet): RedirectResponse
    {
        if ($questionSet->is_default) {
            return back()->withErrors(['set' => 'Không xóa được bộ mặc định. Hãy chọn bộ khác làm mặc định trước.']);
        }

        $questionSet->delete();
        AdminActivity::record(AdminActivity::ACTION_DELETE, AdminActivity::SUBJECT_SET, $questionSet->id, 'Đưa bộ câu "'.$questionSet->name.'" vào thùng rác');

        return redirect()->route('admin.question-sets.index')->with('status', 'Đã đưa bộ "'.$questionSet->name.'" vào thùng rác. Có thể khôi phục ở cuối trang.');
    }

    public function restore(int $id): RedirectResponse
    {
        $set = QuestionSet::onlyTrashed()->findOrFail($id);
        $set->restore();
        AdminActivity::record(AdminActivity::ACTION_RESTORE, AdminActivity::SUBJECT_SET, $set->id, 'Khôi phục bộ câu "'.$set->name.'"');

        return redirect()->route('admin.question-sets.show', $set)->with('status', 'Đã khôi phục bộ "'.$set->name.'".');
    }

    /**
     * Giữ giá trị điểm của thang hiện tại, chỉ đổi nhãn.
     *
     * @param  array<int, string>  $labels
     * @return list<array{value: int|float, label: string}>
     */
    private function scaleWithLabels(QuestionSet $set, array $labels): array
    {
        return collect($set->scaleOptions())
            ->values()
            ->map(fn (array $option, int $index) => ['value' => $option['value'], 'label' => trim((string) ($labels[$index] ?? $option['label'])) ?: $option['label']])
            ->all();
    }
}
