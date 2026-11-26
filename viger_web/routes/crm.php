<?php

use App\Http\Controllers\Crm\AccountController;
use App\Http\Controllers\Crm\ActivityController;
use App\Http\Controllers\Crm\AppointmentController;
use App\Http\Controllers\Crm\AuditLogController;
use App\Http\Controllers\Crm\AuthController;
use App\Http\Controllers\Crm\ConvertedController;
use App\Http\Controllers\Crm\CustomerController;
use App\Http\Controllers\Crm\DashboardController;
use App\Http\Controllers\Crm\LeadController;
use App\Http\Controllers\Crm\NoteController;
use App\Http\Controllers\Crm\NotificationController;
use App\Http\Controllers\Crm\PipelineController;
use App\Http\Controllers\Crm\ReportController;
use App\Http\Controllers\Crm\RoleController;
use App\Http\Controllers\Crm\SettingController;
use App\Http\Controllers\Crm\SourceController;
use App\Http\Controllers\Crm\StaffController;
use App\Http\Controllers\Crm\StudentController;
use App\Models\Appointment;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
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
|
| PHÂN QUYỀN: mỗi nhóm route gắn middleware crm.can:<quyền> (bảng crm_role_permission).
| Tham số {lead}, {appointment}, {note} tự kiểm tra phạm vi dữ liệu (User::canSeeLead) khi nạp:
| khách ngoài phạm vi => 403, nên controller không cần kiểm tra lại.
*/

$crmDomain = config('app.crm_domain');

$crm = $crmDomain ? Route::domain($crmDomain) : Route::prefix('crm');

Route::pattern('lead', '[0-9]+');
Route::pattern('member', '[0-9]+');
Route::pattern('appointment', '[0-9]+');
Route::pattern('note', '[0-9]+');
Route::pattern('team', '[0-9]+');
Route::model('member', User::class);

$guardLead = function (?Lead $lead) {
    abort_unless($lead && auth()->user()?->canSeeLead($lead), 403, 'Khách hàng này không thuộc phạm vi của bạn.');
};
Route::bind('lead', function ($id) use ($guardLead) {
    $lead = Lead::findOrFail($id);
    $guardLead($lead);

    return $lead;
});
Route::bind('appointment', function ($id) use ($guardLead) {
    $appointment = Appointment::with('lead')->findOrFail($id);
    $guardLead($appointment->lead);

    return $appointment;
});
Route::bind('note', function ($id) use ($guardLead) {
    $note = LeadActivity::with('lead')->findOrFail($id);
    $guardLead($note->lead);

    return $note;
});

$crm->name('crm.')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth', 'crm.password'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Tài khoản của tôi
        Route::get('/account/password', [AccountController::class, 'editPassword'])->name('account.password');
        Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
        Route::post('/notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');

        Route::get('/', DashboardController::class)->middleware('crm.can:dashboard.view')->name('dashboard');

        // ---- Xem khách hàng (theo phạm vi) ----
        Route::middleware('crm.can:leads.view')->group(function () {
            Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('/students', [StudentController::class, 'index'])->name('students.index');
            Route::get('/converted', [ConvertedController::class, 'index'])->name('converted.index');
            Route::get('/pipeline', [PipelineController::class, 'index'])->name('pipeline.index');
            Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
            Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        });

        // ---- Thêm / sửa / xoá / xuất khách ----
        Route::get('/leads/create', [LeadController::class, 'create'])->middleware('crm.can:leads.create')->name('leads.create');
        Route::post('/leads', [LeadController::class, 'store'])->middleware('crm.can:leads.create')->name('leads.store');
        Route::get('/leads/export', [LeadController::class, 'export'])->middleware('crm.can:leads.export')->name('leads.export');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->middleware('crm.can:leads.view')->name('leads.show');
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->middleware('crm.can:leads.delete')->name('leads.destroy');

        Route::middleware('crm.can:leads.update')->group(function () {
            Route::get('/leads/{lead}/edit/{section}', [LeadController::class, 'edit'])->name('leads.edit');
            Route::put('/leads/{lead}/edit/{section}', [LeadController::class, 'update'])->name('leads.update');
            Route::post('/leads/{lead}/notes', [LeadController::class, 'storeNote'])->name('leads.notes.store');
            Route::patch('/pipeline/{lead}', [PipelineController::class, 'move'])->name('pipeline.move');
            Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
            Route::put('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
            Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
        });

        // ---- Lịch hẹn ----
        Route::middleware('crm.can:appointments.manage')->group(function () {
            Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
            Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
            Route::get('/appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
            Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
            Route::post('/appointments/{appointment}/status', [AppointmentController::class, 'status'])->name('appointments.status');
        });

        // ---- Lịch sử tư vấn ----
        Route::middleware('crm.can:activities.view')->group(function () {
            Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
            Route::get('/notes', [NoteController::class, 'index'])->name('notes.index');
        });

        // ---- Nguồn & báo cáo ----
        Route::get('/sources/{source}', [SourceController::class, 'show'])->middleware('crm.can:sources.view')->name('sources.show');
        Route::middleware('crm.can:reports.view')->prefix('reports')->name('reports.')->group(function () {
            Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
            Route::get('/consultants', [ReportController::class, 'consultants'])->name('consultants');
            Route::get('/conversion', [ReportController::class, 'conversion'])->name('conversion');
        });

        // ---- Hệ thống ----
        Route::middleware('crm.can:staff.manage')->prefix('staff')->name('staff.')->group(function () {
            Route::get('/', [StaffController::class, 'index'])->name('index');
            Route::get('/create', [StaffController::class, 'create'])->name('create');
            Route::post('/', [StaffController::class, 'store'])->name('store');
            Route::get('/{member}/edit', [StaffController::class, 'edit'])->name('edit');
            Route::put('/{member}', [StaffController::class, 'update'])->name('update');
            Route::post('/{member}/lock', [StaffController::class, 'lock'])->name('lock');
            Route::post('/{member}/unlock', [StaffController::class, 'unlock'])->name('unlock');
            Route::post('/{member}/reset-password', [StaffController::class, 'resetPassword'])->name('reset');
        });

        Route::middleware('crm.can:roles.manage')->prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::put('/', [RoleController::class, 'update'])->name('update');
            Route::post('/teams', [RoleController::class, 'storeTeam'])->name('teams.store');
            Route::put('/teams/{team}', [RoleController::class, 'updateTeam'])->name('teams.update');
            Route::delete('/teams/{team}', [RoleController::class, 'destroyTeam'])->name('teams.destroy');
        });

        Route::middleware('crm.can:settings.manage')->group(function () {
            Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
            Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
            Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
        });
    });
});
