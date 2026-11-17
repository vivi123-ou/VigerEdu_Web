<?php

use App\Http\Controllers\Admin\Assessment\TestSettingsController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\IntroBlockController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PageEditorController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TestController;
use App\Support\AdminMenu;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Luồng làm bài (GĐ3 làm lại: Thông tin → Làm bài → Báo cáo)
Route::get('lam-bai', [TestController::class, 'show'])->name('test.show');

// Khối nhúng cho web chính (iframe + script)
Route::get('embed', [EmbedController::class, 'show'])->name('embed');
Route::get('embed.js', [EmbedController::class, 'script'])->name('embed.script');

Route::prefix('admin')->name('admin.')->middleware('noindex')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('dang-nhap', [LoginController::class, 'create'])->name('login');
        Route::post('dang-nhap', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('dang-xuat', [LoginController::class, 'destroy'])->name('logout');

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

        // ---- Chỉ quản trị viên ----
        Route::middleware('menu:test-settings')->group(function (): void {
            Route::get('test-settings', [TestSettingsController::class, 'edit'])->name('test-settings.edit');
            Route::put('test-settings', [TestSettingsController::class, 'update'])->name('test-settings.update');
            Route::post('test-settings/versions/{version}/restore', [TestSettingsController::class, 'restore'])->name('test-settings.restore');
        });

        // Mục chưa làm: trang "Đang xây dựng – giai đoạn X" (config/admin_menu.php → 'stage')
        Route::get('{section}', SectionController::class)
            ->whereIn('section', AdminMenu::pendingKeys())
            ->name('section');
    });
});
