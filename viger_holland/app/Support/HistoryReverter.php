<?php

namespace App\Support;

use App\Models\AdminActivity;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Làm gì:   "Quay lại bản này" ở khung Lịch sử thay đổi của các trang nội dung: đặt lại giá trị trước lần sửa
 *           (cài đặt hoặc bản ghi), ghi thêm một dòng lịch sử. Nhân viên chỉ quay lại được nội dung trang mình được vào.
 * Ai gọi:   App\Filament\Pages\ContentSettingsPage (bảng lịch sử).
 * Dữ liệu:  admin_activities, settings (hoặc model trong AdminActivity::MODELS).
 */
final class HistoryReverter
{
    /**
     * Mục menu (quyền) ứng với một dòng lịch sử; null = chỉ quản trị viên.
     */
    public static function menuKey(AdminActivity $activity): ?string
    {
        $subject = (string) $activity->subject_type;

        return match (true) {
            $subject === AdminActivity::SUBJECT_GROUPS, $subject === IntroBlocks::SUBJECT => 'intro',
            $subject === AdminActivity::SUBJECT_SETTINGS.'.report' => 'report-pages',
            str_starts_with($subject, AdminActivity::SUBJECT_SETTINGS.'.') => substr($subject, strlen(AdminActivity::SUBJECT_SETTINGS) + 1),
            default => null,
        };
    }

    public static function allows(User $user, AdminActivity $activity): bool
    {
        if (! $activity->canRevert()) {
            return false;
        }

        $menuKey = self::menuKey($activity);

        return $user->isAdmin() || ($menuKey !== null && $user->canAccessMenu($menuKey));
    }

    /**
     * @return bool false khi nội dung hiện tại đã giống bản trước
     */
    public static function revert(AdminActivity $activity): bool
    {
        $label = 'Quay lại bản trước lần sửa lúc '.$activity->created_at->format('H:i d/m/Y');

        if (str_starts_with((string) $activity->subject_type, AdminActivity::SUBJECT_SETTINGS)) {
            $current = [];
            foreach ($activity->before as $key => $value) {
                $current[$key] = Setting::query()->where('key', $key)->value('value');
                $setting = Setting::firstOrNew(['key' => $key], ['group' => 'general']);
                $setting->value = $value;
                $setting->save();
            }
            Cache::forget('holland.public_intro');
            AdminActivity::record(AdminActivity::ACTION_UPDATE, $activity->subject_type, null, $label, $current, $activity->before);

            return true;
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

        return AdminActivity::recordModelChange($model, (string) $activity->subject_type, $label) !== null;
    }
}
