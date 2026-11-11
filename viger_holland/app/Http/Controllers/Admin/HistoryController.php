<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Setting;
use App\Support\IntroBlocks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;

/**
 * "Quay lại bản này" ở khung Lịch sử thay đổi của mọi trang quản trị.
 */
class HistoryController extends Controller
{
    public function revert(AdminActivity $activity): RedirectResponse
    {
        abort_unless($activity->canRevert(), 404);

        // Marketing chỉ quay lại được nội dung trang mà tài khoản được vào.
        $user = auth()->user();
        if (! $user->isAdmin()) {
            $menuKey = match (true) {
                $activity->subject_type === AdminActivity::SUBJECT_GROUPS => 'intro',
                $activity->subject_type === IntroBlocks::SUBJECT => 'intro',
                str_starts_with((string) $activity->subject_type, AdminActivity::SUBJECT_SETTINGS.'.') => substr((string) $activity->subject_type, strlen(AdminActivity::SUBJECT_SETTINGS) + 1),
                default => null,
            };
            abort_unless($menuKey !== null && $user->canAccessMenu($menuKey), 403, 'Tài khoản của bạn không có quyền quay lại nội dung này.');
        }

        $label = 'Quay lại bản trước lần sửa lúc '.$activity->created_at->format('H:i d/m/Y');

        if (str_starts_with((string) $activity->subject_type, AdminActivity::SUBJECT_SETTINGS)) {
            $this->revertSettings($activity, $label);

            return back()->with('status', 'Đã quay lại bản trước.');
        }

        $class = AdminActivity::MODELS[$activity->subject_type] ?? null;
        abort_if($class === null, 404);

        /** @var Model|null $model */
        $model = in_array(SoftDeletes::class, class_uses_recursive($class), true)
            ? $class::withTrashed()->find($activity->subject_id)
            : $class::find($activity->subject_id);
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
