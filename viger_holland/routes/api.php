<?php

use App\Http\Controllers\EmbedController;
use App\Modules\Api\Http\Controllers\CatalogController;
use App\Modules\Api\Http\Controllers\ResultController;
use Illuminate\Support\Facades\Route;

/*
| API v1 (docs/HOP_DONG_KET_NOI.md mục 3, tài liệu: docs/API_HOLLAND.md). 60 lần / phút.
| - public/intro: khối giới thiệu cho web chính, không cần token.
| - results, catalog: cần header X-Api-Token có phạm vi results:read (cấp ở trang "Kết nối API (chatbot)").
*/

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {
    Route::get('public/intro', [EmbedController::class, 'intro'])->name('api.public.intro');

    Route::middleware('api.token:results:read')->group(function (): void {
        Route::get('results/{token}', [ResultController::class, 'show'])->name('api.results.show');
        Route::get('catalog/{type}', [CatalogController::class, 'show'])->whereIn('type', ['types', 'pairs', 'third', 'majors'])->name('api.catalog.show');
    });
});
