<?php

use App\Http\Controllers\Admin\AttemptController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\HollandQuestionController;
use App\Http\Controllers\Admin\InfoQuestionController;
use App\Http\Controllers\Admin\IntroBlockController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PageEditorController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuestionExcelController;
use App\Http\Controllers\Admin\QuestionSetController;
use App\Http\Controllers\Admin\ScoringController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\TestController;
use App\Support\AdminMenu;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Làm bài → nộp bài → xem kết quả
Route::get('lam-bai', [TestController::class, 'show'])->name('test.show');
Route::post('nop-bai', [TestController::class, 'submit'])->middleware('throttle:submit-test')->name('test.submit');
Route::get('ket-qua/{token}', [ResultController::class, 'show'])->middleware('noindex')->name('result.show');

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

        // Quyền theo vai trò: 'menu:{mục}' – marketing (EDITOR) chỉ vào được mục có 'editor' => true (config/admin_menu.php).
        Route::middleware('menu:attempts')->group(function (): void {
            Route::get('attempts', [AttemptController::class, 'index'])->name('attempts.index');
            Route::get('attempts/{attempt}', [AttemptController::class, 'show'])->name('attempts.show');
        });

        // Quay lại bản cũ: controller kiểm tra quyền theo loại nội dung
        Route::post('history/{activity}/revert', [QuestionController::class, 'revert'])->name('history.revert');

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

        Route::middleware('menu:groups')->group(function (): void {
            Route::get('groups', [GroupController::class, 'edit'])->name('groups.edit');
            Route::put('groups', [GroupController::class, 'update'])->name('groups.update');
        });

        // ---- Chỉ quản trị viên ----
        Route::middleware('menu:question-sets')->group(function (): void {
            // Bộ câu hỏi Phần B
            Route::get('question-sets', [QuestionSetController::class, 'index'])->name('question-sets.index');
            Route::get('question-sets/create', [QuestionSetController::class, 'create'])->name('question-sets.create');
            Route::post('question-sets', [QuestionSetController::class, 'store'])->name('question-sets.store');
            Route::get('question-sets/{questionSet}', [QuestionSetController::class, 'show'])->name('question-sets.show');
            Route::get('question-sets/{questionSet}/edit', [QuestionSetController::class, 'edit'])->name('question-sets.edit');
            Route::put('question-sets/{questionSet}', [QuestionSetController::class, 'update'])->name('question-sets.update');
            Route::post('question-sets/{questionSet}/duplicate', [QuestionSetController::class, 'duplicate'])->name('question-sets.duplicate');
            Route::delete('question-sets/{questionSet}', [QuestionSetController::class, 'destroy'])->name('question-sets.destroy');
            Route::post('question-sets/{id}/restore', [QuestionSetController::class, 'restore'])->whereNumber('id')->name('question-sets.restore');

            // Câu Holland trong bộ
            Route::get('question-sets/{questionSet}/questions/create', [HollandQuestionController::class, 'create'])->name('question-sets.questions.create');
            Route::post('question-sets/{questionSet}/questions', [HollandQuestionController::class, 'store'])->name('question-sets.questions.store');
            Route::post('question-sets/{questionSet}/questions/bulk', [HollandQuestionController::class, 'bulkStore'])->name('question-sets.questions.bulk');
            Route::get('holland-questions/{question}/edit', [HollandQuestionController::class, 'edit'])->name('holland-questions.edit');
            Route::get('holland-questions/{question}/card', [HollandQuestionController::class, 'card'])->name('holland-questions.card');
            Route::put('holland-questions/{question}', [HollandQuestionController::class, 'update'])->name('holland-questions.update');

            // Form thông tin khách Phần A
            Route::get('info-form', [InfoQuestionController::class, 'index'])->name('info-questions.index');
            Route::get('info-form/create', [InfoQuestionController::class, 'create'])->name('info-questions.create');
            Route::post('info-form', [InfoQuestionController::class, 'store'])->name('info-questions.store');
            Route::get('info-form/{question}/edit', [InfoQuestionController::class, 'edit'])->name('info-questions.edit');
            Route::get('info-form/{question}/card', [InfoQuestionController::class, 'card'])->name('info-questions.card');
            Route::put('info-form/{question}', [InfoQuestionController::class, 'update'])->name('info-questions.update');

            // Dùng chung cho câu Phần A và B
            Route::patch('questions/{question}/toggle', [QuestionController::class, 'toggle'])->name('questions.toggle');
            Route::delete('questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
            Route::post('questions/{question}/duplicate', [QuestionController::class, 'duplicate'])->name('questions.duplicate');
            Route::post('questions/{id}/restore', [QuestionController::class, 'restore'])->whereNumber('id')->name('questions.restore');
            Route::post('questions/reorder', [QuestionController::class, 'reorder'])->name('questions.reorder');
        });

        Route::middleware('menu:scoring')->group(function (): void {
            Route::get('scoring', [ScoringController::class, 'edit'])->name('scoring.edit');
            Route::put('scoring', [ScoringController::class, 'update'])->name('scoring.update');
            Route::post('scoring/reset', [ScoringController::class, 'reset'])->name('scoring.reset');
        });

        Route::middleware('menu:excel')->group(function (): void {
            Route::get('excel', [QuestionExcelController::class, 'index'])->name('excel.index');
            Route::get('excel/export/{questionSet}', [QuestionExcelController::class, 'export'])->name('excel.export');
            Route::post('excel/preview', [QuestionExcelController::class, 'preview'])->name('excel.preview');
            Route::post('excel/import', [QuestionExcelController::class, 'import'])->name('excel.import');
        });

        Route::get('{section}', SectionController::class)
            ->whereIn('section', AdminMenu::pendingKeys())
            ->name('section');
    });
});
