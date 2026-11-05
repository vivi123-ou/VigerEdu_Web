<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnswerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InfoQuestionRequest;
use App\Models\AdminActivity;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Form "Nhận kết quả" (Phần A): câu admin thêm tự hiện ở form, tự được kiểm tra khi nộp, tự gửi CRM.
 */
class InfoQuestionController extends Controller
{
    public function index(): View
    {
        return view('admin.info-questions.index', [
            'questions' => Question::part(Question::PART_INFO)->ordered()->get(),
            'trashed' => Question::onlyTrashed()->part(Question::PART_INFO)->latest('deleted_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.info-questions.form', [
            'question' => new Question(['answer_type' => AnswerType::Text, 'is_active' => true]),
            'conditionQuestions' => $this->conditionQuestions(),
            'activities' => collect(),
        ]);
    }

    public function store(InfoQuestionRequest $request): RedirectResponse
    {
        $question = Question::create([
            ...$request->questionAttributes(),
            'code' => Question::nextCode(Question::PART_INFO),
            'part' => Question::PART_INFO,
            'sort_order' => $this->defaultSortOrder(),
        ]);

        AdminActivity::record(AdminActivity::ACTION_CREATE, AdminActivity::SUBJECT_QUESTION, $question->id, 'Thêm câu thông tin '.$question->shortLabel());

        return redirect()->route('admin.info-questions.index')
            ->with('status', 'Đã thêm câu '.$question->code.'. Câu mới hiện ngay ở form "Nhận kết quả"'.($question->crmKey() ? ' và được gửi sang CRM vào trường '.$question->crmKey() : '').'.');
    }

    public function edit(Question $question): View
    {
        abort_if($question->isHolland(), 404);

        return view('admin.info-questions.form', [
            'question' => $question,
            'conditionQuestions' => $this->conditionQuestions()->reject(fn (Question $other) => $other->id === $question->id),
            'activities' => AdminActivity::forSubject(AdminActivity::SUBJECT_QUESTION, $question->id),
        ]);
    }

    public function update(InfoQuestionRequest $request, Question $question): RedirectResponse
    {
        abort_if($question->isHolland(), 404);

        $attributes = $request->questionAttributes();

        if ($question->lead_field === 'consent') {
            $attributes['is_active'] = true;
            $attributes['answer_type'] = AnswerType::Consent;
            $attributes['lead_field'] = 'consent';
        }

        $question->fill($attributes)->save();
        AdminActivity::recordModelChange($question, AdminActivity::SUBJECT_QUESTION, 'Sửa câu thông tin '.$question->shortLabel());

        return redirect()->route('admin.info-questions.index')->with('status', 'Đã lưu câu '.$question->code.'.');
    }

    /**
     * Câu có lựa chọn (dùng làm điều kiện "chỉ hiện khi…").
     *
     * @return Collection<int, Question>
     */
    private function conditionQuestions(): Collection
    {
        return Question::part(Question::PART_INFO)->ordered()->get()
            ->filter(fn (Question $question) => in_array($question->answer_type, [AnswerType::Single, AnswerType::Boolean], true))
            ->values();
    }

    /**
     * Câu mới đứng trước ô đồng ý (ô đồng ý luôn ở cuối form).
     */
    private function defaultSortOrder(): int
    {
        $consentOrder = Question::part(Question::PART_INFO)->where('lead_field', 'consent')->value('sort_order');
        $maxOther = (int) Question::withTrashed()->part(Question::PART_INFO)->where(fn ($query) => $query->whereNull('lead_field')->orWhere('lead_field', '!=', 'consent'))->max('sort_order');

        $next = $maxOther + 1;

        return $consentOrder !== null && $next >= $consentOrder ? max(0, $consentOrder - 1) : $next;
    }
}
