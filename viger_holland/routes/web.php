<?php

use App\Http\Controllers\Admin\ContentPreviewController;
use App\Http\Controllers\Admin\IntroBlockController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PageEditorController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\HomeController;
use App\Modules\Assessment\Http\Controllers\QuizController;
use App\Modules\Respondent\Http\Controllers\ReportController;
use App\Modules\Tracking\Http\Controllers\TrackController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Luồng khách (H3): Bắt đầu → Làm bài từng trang → Kết quả sơ lược.
Route::post('lam-bai/bat-dau', [QuizController::class, 'start'])->middleware('throttle:quiz-start')->name('test.start');
Route::get('lam-bai', [QuizController::class, 'show'])->name('test.show');
Route::post('lam-bai', [QuizController::class, 'submit'])->middleware('throttle:quiz-page')->name('test.submit');
Route::get('ket-qua', [QuizController::class, 'result'])->name('test.result');

// Luồng khách (H4): Form thông tin → Mã xác nhận qua email → Báo cáo đầy đủ (xem trong thẻ đã mở, đóng thẻ là mất). Không có trang nào chứa mã trên link.
Route::middleware('noindex')->group(function (): void {
    Route::get('nhan-bao-cao', [ReportController::class, 'form'])->name('report.request');
    Route::post('nhan-bao-cao', [ReportController::class, 'submit'])->middleware('throttle:report-form')->name('report.submit');
    Route::get('xac-nhan', [ReportController::class, 'code'])->name('report.code');
    Route::post('xac-nhan', [ReportController::class, 'verify'])->middleware('throttle:report-code')->name('report.verify');
    Route::post('xac-nhan/gui-lai', [ReportController::class, 'resend'])->middleware('throttle:report-resend')->name('report.resend');
    Route::get('bao-cao', [ReportController::class, 'show'])->name('report.show');
    Route::post('bao-cao/xem', [ReportController::class, 'view'])->middleware('throttle:report-view')->name('report.view');
});

// Hành vi từ trình duyệt (bấm Gọi / Zalo trên báo cáo), gửi kèm sang CRM (H5).
Route::post('t', TrackController::class)->middleware('throttle:track')->name('tracking.event');

// Khối nhúng cho web chính (iframe + script)
Route::get('embed', [EmbedController::class, 'show'])->name('embed');
Route::get('embed.js', [EmbedController::class, 'script'])->name('embed.script');

/*
| Khu quản trị /admin do Filament dựng (app/Providers/Filament/AdminPanelProvider.php, trang ở thư mục Filament của từng module).
| Dưới đây chỉ còn các đường phụ trợ, đăng nhập dùng chung với Filament:
|   – "Xem trước" nội dung chưa lưu (mở ở thẻ mới);
|   – "Sửa trực tiếp trên trang" (trang khách ở chế độ sửa) và các lệnh nó gọi: lưu, ẩn / hiện, đổi thứ tự khối;
|   – hộp "Chọn ảnh" trong chế độ sửa trực tiếp: danh sách ảnh, tải ảnh lên (JSON).
*/
Route::prefix('admin')->name('admin.')->middleware(['noindex', 'auth', 'admin'])->group(function (): void {
    Route::get('xem-truoc/{token}', ContentPreviewController::class)->where('token', '[A-Za-z0-9]{40}')->name('content.preview');

    // Sửa trực tiếp trên trang (controller kiểm tra quyền theo từng trang / từng ô)
    Route::get('sua-trang/{page}', [PageEditorController::class, 'show'])
        ->whereIn('page', array_keys(PageEditorController::PAGES))->name('page-editor.show');
    Route::post('sua-trang', [PageEditorController::class, 'save'])->name('page-editor.save');

    Route::middleware('menu:intro')->group(function (): void {
        Route::post('intro-blocks/reorder', [IntroBlockController::class, 'reorder'])->name('intro-blocks.reorder');
        Route::patch('intro-blocks/{id}/toggle', [IntroBlockController::class, 'toggle'])->name('intro-blocks.toggle');
    });

    Route::middleware('menu:media')->group(function (): void {
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
    });
});
