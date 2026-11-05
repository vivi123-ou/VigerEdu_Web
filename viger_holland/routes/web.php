<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\HomeController;
use App\Support\AdminMenu;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::prefix('admin')->name('admin.')->middleware('noindex')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('dang-nhap', [LoginController::class, 'create'])->name('login');
        Route::post('dang-nhap', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('dang-xuat', [LoginController::class, 'destroy'])->name('logout');

        Route::get('{section}', SectionController::class)
            ->whereIn('section', AdminMenu::pendingKeys())
            ->name('section');
    });
});
