<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnswerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InfoQuestionRequest;
use App\Models\AdminActivity;
use App\Models\Question;
use App\Support\QuestionSummary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Form "Nhận kết quả" (câu thông tin khách): câu admin thêm tự hiện ở form, tự được kiểm tra khi nộp, tự gửi CRM.
 * Danh sách dạng thẻ (kiểu Google Form): bấm thẻ để sửa tại chỗ, tự lưu.
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

    /**
     * Form sửa trong thẻ câu hỏi, tải khi bấm mở thẻ.
     */
    public function card(Question $question): View
    {
        abort_if($question->isHolland(), 404);

        return view('admin.partials.question-card-form', [
            'action' => route('admin.info-questions.update', $question),
            'fieldsView' => 'admin.info-questions.fields',
            'fieldsData' => ['question' => $question, 'conditionQuestions' => $this->conditionQuestions(), 'uid' => 'q'.$question->id],
        ]);
    }

    public static function cardHtml(Question $question): string
    {
        $isConsent = $question->lead_field === 'consent';

        return view('admin.partials.question-card', [
            'question' => $question,
            'meta' => QuestionSummary::info($question),
            'formUrl' => route('admin.info-questions.card', $question),
            'duplicateUrl' => $isConsent ? null : route('admin.questions.duplicate', $question),
            'canDelete' => ! $isConsent,
        ])->render();
    }

    public function store(InfoQuestionRequest $request): RedirectResponse|JsonResponse
    {
        $question = Question::create([
            ...$request->questionAttributes(),
            'code' => Question::nextCode(Question::PART_INFO),
            'part' => Question::PART_INFO,
            'sort_order' => $this->defaultSortOrder(),
        ]);

        AdminActivity::record(AdminActivity::ACTION_CREATE, AdminActivity::SUBJECT_QUESTION, $question->id, 'Thêm câu thông tin '.$question->shortLabel());

        if ($request->wantsJson()) {
            return response()->json(['card' => self::cardHtml($question)]);
        }

        return redirect()->route('admin.info-questions.index')
            ->with('status', 'Đã thêm câu. Câu mới hiện ngay ở form "Nhận kết quả" và câu trả lời được lưu vào hồ sơ khách.');
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

    public function update(InfoQuestionRequest $request, Question $question): RedirectResponse|JsonResponse
    {
        abort_if($question->isHolland(), 404);

        $attributes = $request->questionAttributes();

        if ($question->lead_field === 'consent') {
            $attributes['is_active'] = true;
            $attributes['answer_type'] = AnswerType::Consent;
            $attributes['lead_field'] = 'consent';
        }

        $question->fill($attributes)->save();
        AdminActivity::recordModelChange($question, AdminActivity::SUBJECT_QUESTION, 'Sửa câu thông tin '.$question->shortLabel(), mergeRecent: $request->wantsJson());

        if ($request->wantsJson()) {
            return response()->json(['text' => $question->text, 'meta' => QuestionSummary::info($question), 'is_active' => $question->is_active]);
        }

        return redirect()->route('admin.info-questions.index')->with('status', 'Đã lưu câu hỏi.');
    }

    /**
     * Câu có lựa chọn (dùng làm điều kiện "chỉ hỏi khi…").
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
