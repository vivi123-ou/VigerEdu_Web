<?php

use App\Http\Controllers\Admin\AttemptController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\SectionController;
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

Route::prefix('admin')->name('admin.')->middleware('noindex')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('dang-nhap', [LoginController::class, 'create'])->name('login');
        Route::post('dang-nhap', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('dang-xuat', [LoginController::class, 'destroy'])->name('logout');

        Route::get('attempts', [AttemptController::class, 'index'])->name('attempts.index');
        Route::get('attempts/{attempt}', [AttemptController::class, 'show'])->name('attempts.show');

        Route::get('{section}', SectionController::class)
            ->whereIn('section', AdminMenu::pendingKeys())
            ->name('section');
    });
});
