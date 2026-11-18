<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\IntroBlockController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PageEditorController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\HomeController;
use App\Modules\Assessment\Http\Controllers\QuizController;
use App\Modules\Respondent\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Luồng khách (H3): Bắt đầu → Làm bài từng trang → Kết quả sơ lược.
Route::post('lam-bai/bat-dau', [QuizController::class, 'start'])->middleware('throttle:quiz-start')->name('test.start');
Route::get('lam-bai', [QuizController::class, 'show'])->name('test.show');
Route::post('lam-bai', [QuizController::class, 'submit'])->middleware('throttle:quiz-page')->name('test.submit');
Route::get('ket-qua', [QuizController::class, 'result'])->name('test.result');

// Luồng khách (H4): Form thông tin → Mã xác nhận qua email → Báo cáo đầy đủ (chỉ hiện một lần). Không có trang nào chứa mã trên link.
Route::middleware('noindex')->group(function (): void {
    Route::get('nhan-bao-cao', [ReportController::class, 'form'])->name('report.request');
    Route::post('nhan-bao-cao', [ReportController::class, 'submit'])->middleware('throttle:report-form')->name('report.submit');
    Route::get('xac-nhan', [ReportController::class, 'code'])->name('report.code');
    Route::post('xac-nhan', [ReportController::class, 'verify'])->middleware('throttle:report-code')->name('report.verify');
    Route::post('xac-nhan/gui-lai', [ReportController::class, 'resend'])->middleware('throttle:report-resend')->name('report.resend');
    Route::get('bao-cao', [ReportController::class, 'show'])->name('report.show');
});

// Khối nhúng cho web chính (iframe + script)
Route::get('embed', [EmbedController::class, 'show'])->name('embed');
Route::get('embed.js', [EmbedController::class, 'script'])->name('embed.script');

/*
| Khu quản trị chính (/admin, đăng nhập, bảng điều khiển, câu hỏi, cài đặt, tài khoản) do Filament dựng:
| app/Providers/Filament/AdminPanelProvider.php. Dưới đây là các trang nội dung làm từ trước, chưa chuyển sang
| Filament (sẽ chuyển dần ở H6). Đăng nhập dùng chung với Filament.
*/
Route::prefix('admin')->name('admin.')->middleware(['noindex', 'auth', 'admin'])->group(function (): void {
    // Quay lại bản cũ: controller kiểm tra quyền theo loại nội dung
    Route::post('history/{activity}/revert', [HistoryController::class, 'revert'])->name('history.revert');

    // Sửa trực tiếp trên trang (controller kiểm tra quyền theo từng trang / từng ô)
    Route::get('sua-trang/{page}', [PageEditorController::class, 'show'])
        ->whereIn('page', array_keys(PageEditorController::PAGES))->name('page-editor.show');
    Route::post('sua-trang', [PageEditorController::class, 'save'])->name('page-editor.save');

    // Nội dung trang (config/admin_content.php): controller kiểm tra quyền theo từng trang
    $contentPages = array_keys(config('admin_content'));
    Route::get('content/{page}', [ContentController::class, 'edit'])->whereIn('page', $contentPages)->name('content.edit');
    Route::put('content/{page}', [ContentController::class, 'update'])->whereIn('page', $contentPages)->name('content.update');
    Route::post('content/{page}/preview', [ContentController::class, 'preview'])->whereIn('page', $contentPages)->name('content.preview');

    Route::middleware('menu:intro')->group(function (): void {
        Route::get('intro-blocks', [IntroBlockController::class, 'index'])->name('intro-blocks.index');
        Route::post('intro-blocks', [IntroBlockController::class, 'store'])->name('intro-blocks.store');
        Route::post('intro-blocks/reorder', [IntroBlockController::class, 'reorder'])->name('intro-blocks.reorder');
        Route::get('intro-blocks/{id}/edit', [IntroBlockController::class, 'edit'])->name('intro-blocks.edit');
        Route::put('intro-blocks/{id}', [IntroBlockController::class, 'update'])->name('intro-blocks.update');
        Route::post('intro-blocks/{id}/preview', [IntroBlockController::class, 'preview'])->name('intro-blocks.preview');
        Route::patch('intro-blocks/{id}/toggle', [IntroBlockController::class, 'toggle'])->name('intro-blocks.toggle');
        Route::delete('intro-blocks/{id}', [IntroBlockController::class, 'destroy'])->name('intro-blocks.destroy');
    });

    Route::middleware('menu:media')->group(function (): void {
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
        Route::post('media/{id}/restore', [MediaController::class, 'restore'])->whereNumber('id')->name('media.restore');
    });
});
