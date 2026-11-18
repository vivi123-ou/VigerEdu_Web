<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\IntroBlockController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PageEditorController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\HomeController;
use App\Modules\Assessment\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Luồng khách (H3): Bắt đầu → Làm bài từng trang → Kết quả sơ lược. Thông tin, mã email, báo cáo đầy đủ: H4.
Route::post('lam-bai/bat-dau', [QuizController::class, 'start'])->middleware('throttle:10,1')->name('test.start');
Route::get('lam-bai', [QuizController::class, 'show'])->name('test.show');
Route::post('lam-bai', [QuizController::class, 'submit'])->middleware('throttle:30,1')->name('test.submit');
Route::get('ket-qua', [QuizController::class, 'result'])->name('test.result');
Route::view('nhan-bao-cao', 'public.updating')->name('report.request'); // H4: form thông tin + mã email

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
