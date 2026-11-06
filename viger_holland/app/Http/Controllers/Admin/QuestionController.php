<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\Setting;
use App\Support\IntroBlocks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Thao tác dùng chung cho câu Phần A và Phần B: bật/tắt, thùng rác, sắp xếp, quay lại bản cũ.
 */
class QuestionController extends Controller
{
    public function toggle(Question $question): RedirectResponse
    {
        if ($question->is_active && $question->lead_field === 'consent') {
            return back()->withErrors(['question' => 'Không tắt được ô đồng ý: thiếu ô này thì không được lưu thông tin khách.']);
        }

        $question->update(['is_active' => ! $question->is_active]);
        AdminActivity::recordModelChange($question, AdminActivity::SUBJECT_QUESTION, ($question->is_active ? 'Bật' : 'Tắt').' câu '.$question->shortLabel());

        return back()->with('status', ($question->is_active ? 'Đã bật' : 'Đã tắt').' câu '.$question->code.'.');
    }

    public function destroy(Request $request, Question $question): RedirectResponse|JsonResponse
    {
        if ($question->lead_field === 'consent') {
            $message = 'Không xóa được ô đồng ý: thiếu ô này thì không được lưu thông tin khách.';

            return $request->wantsJson() ? response()->json(['message' => $message], 422) : back()->withErrors(['question' => $message]);
        }

        $question->delete();
        AdminActivity::record(AdminActivity::ACTION_DELETE, AdminActivity::SUBJECT_QUESTION, $question->id, 'Đưa câu '.$question->shortLabel().' vào thùng rác');

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã đưa câu vào thùng rác.']);
        }

        return back()->with('status', 'Đã đưa câu '.$question->code.' vào thùng rác. Có thể khôi phục ở cuối trang.');
    }

    /**
     * Nhân đôi câu (như Google Form): bản sao đứng ngay sau câu gốc, mã câu mới.
     */
    public function duplicate(Request $request, Question $question): RedirectResponse|JsonResponse
    {
        abort_if($question->lead_field === 'consent', 422, 'Không nhân đôi được ô đồng ý.');

        $copy = DB::transaction(function () use ($question): Question {
            $siblings = Question::query()->where('part', $question->part)
                ->when($question->isHolland(), fn ($query) => $query->where('question_set_id', $question->question_set_id))
                ->where('sort_order', '>', $question->sort_order);
            $siblings->increment('sort_order');

            $copy = $question->replicate();
            $copy->code = Question::nextCode($question->part);
            $copy->sort_order = $question->sort_order + 1;
            // Mục lưu hồ sơ khách không được trùng: bản sao của câu thông tin khách chưa lưu vào CRM, admin chọn lại.
            $copy->lead_field = null;
            $copy->extra_field_key = null;
            $copy->save();

            return $copy;
        });

        AdminActivity::record(AdminActivity::ACTION_CREATE, AdminActivity::SUBJECT_QUESTION, $copy->id, 'Nhân đôi câu '.$question->shortLabel());

        if ($request->wantsJson()) {
            return response()->json(['card' => $copy->isHolland() ? HollandQuestionController::cardHtml($copy) : InfoQuestionController::cardHtml($copy)]);
        }

        return back()->with('status', 'Đã nhân đôi câu.');
    }

    public function restore(int $id): RedirectResponse
    {
        $question = Question::onlyTrashed()->findOrFail($id);
        $question->restore();
        AdminActivity::record(AdminActivity::ACTION_RESTORE, AdminActivity::SUBJECT_QUESTION, $question->id, 'Khôi phục câu '.$question->shortLabel());

        return back()->with('status', 'Đã khôi phục câu '.$question->code.'.');
    }

    /**
     * Lưu thứ tự sau khi kéo thả: order[] = danh sách id theo thứ tự mới.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer'],
        ], ['order.required' => 'Chưa có thứ tự để lưu.']);

        $ids = array_values(array_unique($validated['order']));
        $questions = Question::whereIn('id', $ids)->get()->keyBy('id');

        // Chỉ sắp xếp trong cùng một phần / cùng một bộ.
        if ($questions->pluck('part')->unique()->count() > 1 || $questions->pluck('question_set_id')->unique()->count() > 1) {
            return back()->withErrors(['order' => 'Thứ tự không hợp lệ.']);
        }

        DB::transaction(function () use ($ids, $questions): void {
            foreach ($ids as $index => $id) {
                $questions->get($id)?->update(['sort_order' => ($index + 1) * 10]);
            }
        });

        $first = $questions->first();
        AdminActivity::record(AdminActivity::ACTION_UPDATE, $first?->isHolland() ? AdminActivity::SUBJECT_SET : AdminActivity::SUBJECT_QUESTION,
            $first?->isHolland() ? $first->question_set_id : null, 'Sắp xếp lại thứ tự '.count($ids).' câu');

        return back()->with('status', 'Đã lưu thứ tự mới.');
    }

    /**
     * Quay lại bản trước của một lần sửa (câu hỏi, bộ câu hoặc cài đặt).
     */
    public function revert(AdminActivity $activity): RedirectResponse
    {
        abort_unless($activity->canRevert(), 404);

        // Marketing chỉ quay lại được nội dung trang (trang giới thiệu, kết quả, email, khối nhúng, mô tả nhóm).
        $user = auth()->user();
        if (! $user->isAdmin()) {
            $menuKey = match (true) {
                $activity->subject_type === AdminActivity::SUBJECT_GROUPS => 'groups',
                $activity->subject_type === IntroBlocks::SUBJECT => 'intro',
                str_starts_with((string) $activity->subject_type, AdminActivity::SUBJECT_SETTINGS.'.') => substr((string) $activity->subject_type, strlen(AdminActivity::SUBJECT_SETTINGS) + 1),
                default => 'question-sets',
            };
            abort_unless($user->canAccessMenu($menuKey), 403, 'Tài khoản của bạn không có quyền quay lại nội dung này.');
        }

        $label = 'Quay lại bản trước lần sửa lúc '.$activity->created_at->format('H:i d/m/Y');

        if (str_starts_with((string) $activity->subject_type, AdminActivity::SUBJECT_SETTINGS)) {
            $this->revertSettings($activity, $label);

            return back()->with('status', 'Đã quay lại bản trước.');
        }

        /** @var Model|null $model */
        $model = match ($activity->subject_type) {
            AdminActivity::SUBJECT_QUESTION => Question::withTrashed()->find($activity->subject_id),
            AdminActivity::SUBJECT_SET => QuestionSet::withTrashed()->find($activity->subject_id),
            default => null,
        };

        abort_if($model === null, 404);

        $model->setRawAttributes(array_replace($model->getAttributes(), $activity->before), sync: false);
        $model->save();

        $saved = AdminActivity::recordModelChange($model, (string) $activity->subject_type, $label);

        return back()->with('status', $saved ? 'Đã quay lại bản trước.' : 'Nội dung hiện tại đã giống bản trước, không có gì thay đổi.');
    }

    private function revertSettings(AdminActivity $activity, string $label): void
    {
        $current = [];

        foreach ($activity->before as $key => $value) {
            $current[$key] = Setting::query()->where('key', $key)->value('value');
            $setting = Setting::firstOrNew(['key' => $key], ['group' => 'general']);
            $setting->value = $value;
            $setting->save();
        }

        AdminActivity::record(AdminActivity::ACTION_UPDATE, $activity->subject_type, null, $label, $current, $activity->before);
    }
}
