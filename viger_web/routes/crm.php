<?php

use App\Http\Controllers\Crm\AuthController;
use App\Http\Controllers\Crm\DashboardController;
use App\Http\Controllers\Crm\LeadController;
use App\Http\Controllers\Crm\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Viger CRM
|--------------------------------------------------------------------------
| Production: CRM_DOMAIN=crm.viger.edu.vn  => https://crm.viger.edu.vn/
| Local:      CRM_DOMAIN=crm.localhost     => http://crm.localhost:8000/
| Không đặt CRM_DOMAIN => fallback về đường dẫn /crm trên domain chính.
|
| File này được nạp ở ĐẦU routes/web.php: route "/" và "/login" của web chính
| khớp mọi domain, nên route CRM phải đăng ký trước để được ưu tiên.
*/

$crmDomain = config('app.crm_domain');

$crm = $crmDomain ? Route::domain($crmDomain) : Route::prefix('crm');

Route::pattern('lead', '[0-9]+');

$crm->name('crm.')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('/notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');

        // Khách hàng tiềm năng
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
        Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
        Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
        Route::get('/leads/{lead}/edit/{section}', [LeadController::class, 'edit'])->name('leads.edit');
        Route::put('/leads/{lead}/edit/{section}', [LeadController::class, 'update'])->name('leads.update');
        Route::post('/leads/{lead}/notes', [LeadController::class, 'storeNote'])->name('leads.notes.store');
    });
});
