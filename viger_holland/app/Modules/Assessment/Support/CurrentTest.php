<?php

namespace App\Modules\Assessment\Support;

use App\Modules\Assessment\Models\TestSession;
use Illuminate\Http\Request;

/**
 * Làm gì:   Lượt làm bài của trình duyệt đang mở trang: mã lượt nằm trong phiên của trình duyệt (session), không có trên
 *           địa chỉ trang nên không chia sẻ / đoán được.
 * Ai gọi:   QuizController (làm bài, kết quả sơ lược), ReportController (form, mã xác nhận, báo cáo đầy đủ).
 * Dữ liệu:  session "holland.test" = test_sessions.uuid.
 */
final class CurrentTest
{
    public const SESSION_KEY = 'holland.test';

    public static function get(Request $request): ?TestSession
    {
        $uuid = $request->session()->get(self::SESSION_KEY);

        return is_string($uuid) ? TestSession::query()->where('uuid', $uuid)->first() : null;
    }

    public static function remember(Request $request, TestSession $session): void
    {
        $request->session()->put(self::SESSION_KEY, $session->uuid);
        $request->session()->regenerate();
    }
}
