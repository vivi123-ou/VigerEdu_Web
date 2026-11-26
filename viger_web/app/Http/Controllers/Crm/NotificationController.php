<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Làm gì:   Thông báo trên chuông header CRM, lấy từ lịch sử tư vấn (lead_activities).
 *           - Quản trị viên: mọi hoạt động; người khác: hoạt động trên khách được giao cho mình.
 *           - Không báo hoạt động do chính mình thực hiện.
 * Ai gọi:   layout CRM (feed()); JS khi mở chuông (markRead()).
 * Dữ liệu:  đọc lead_activities, leads; mốc "đã xem" lưu trong cache theo từng user.
 */
class NotificationController extends Controller
{
    private const LIMIT = 8;

    /**
     * @return array{items: Collection<int, LeadActivity>, unread: int}
     */
    public static function feed(User $user): array
    {
        $query = LeadActivity::query()
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $user->id))
            ->whereHas('lead', fn ($l) => $l->visibleTo($user));

        $seenAt = self::seenAt($user);

        return [
            'items' => (clone $query)->with(['lead', 'user'])->latest()->latest('id')->limit(self::LIMIT)->get(),
            'unread' => (clone $query)->when($seenAt, fn ($q) => $q->where('created_at', '>', $seenAt))->count(),
            'seenAt' => $seenAt,
        ];
    }

    /** Đánh dấu đã xem tất cả (gọi khi mở chuông). */
    public function markRead(Request $request): JsonResponse
    {
        Cache::forever(self::cacheKey($request->user()), now()->toIso8601String());

        return response()->json(['unread' => 0]);
    }

    private static function seenAt(User $user): ?Carbon
    {
        $value = Cache::get(self::cacheKey($user));

        return $value ? Carbon::parse($value) : null;
    }

    private static function cacheKey(User $user): string
    {
        return "crm.notifications.seen_at.{$user->id}";
    }
}
