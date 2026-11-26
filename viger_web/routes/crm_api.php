<?php

use App\Http\Controllers\Crm\Api\EventIntakeController;
use App\Http\Controllers\Crm\Api\LeadIntakeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API của CRM — https://crm.viger.edu.vn/api/v1 (HOP_DONG_KET_NOI mục 2)
|--------------------------------------------------------------------------
| Nhóm middleware "api" (không session, không CSRF). Nạp từ bootstrap/app.php.
| TẠM THỜI nằm trong viger_web cho tới khi viger_crm thành app riêng.
*/

$crmDomain = config('app.crm_domain');
$api = $crmDomain ? Route::domain($crmDomain) : Route::prefix('crm');

$api->prefix('api/v1')->name('crm.api.')->middleware('throttle:60,1')->group(function () {
    Route::post('/leads', [LeadIntakeController::class, 'store'])
        ->middleware('crm.token:leads:write')
        ->name('leads.store');

    // Hành vi sau khi đã có customer_code (Holland: xem lại báo cáo, bấm Gọi / Zalo…)
    Route::post('/events', [EventIntakeController::class, 'store'])
        ->middleware('crm.token:events:write')
        ->name('events.store');

    // Nút "Kiểm tra kết nối" của các kênh
    Route::get('/ping', fn (Request $request) => response()->json(['ok' => true, 'channel' => $request->attributes->get('crm_channel')]))
        ->middleware('crm.token:any')
        ->name('ping');
});
