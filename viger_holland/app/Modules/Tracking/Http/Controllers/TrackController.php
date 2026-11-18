<?php

namespace App\Modules\Tracking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assessment\Support\CurrentTest;
use App\Modules\Tracking\Services\Tracker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Làm gì:   Nhận hành vi từ trình duyệt (POST /t): hiện chỉ nhận bấm nút Gọi / Zalo trên báo cáo (config crm.browser_events).
 *           Gắn với lượt làm bài của trình duyệt, chỉ khi khách đã nhập đúng mã; loại lạ thì bỏ qua. Luôn trả 204.
 * Ai gọi:   khach.js (navigator.sendBeacon), route tracking.event (giới hạn 60 lần / phút).
 * Dữ liệu:  tracking_events (qua Tracker).
 */
class TrackController extends Controller
{
    public function __invoke(Request $request, Tracker $tracker): Response
    {
        $session = CurrentTest::get($request);
        $type = (string) $request->input('type');

        if ($session !== null && $session->verified_at !== null && in_array($type, config('crm.browser_events', []), true)) {
            $tracker->record($session, $type);
        }

        return response()->noContent();
    }
}
