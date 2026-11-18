<?php

use App\Http\Controllers\EmbedController;
use Illuminate\Support\Facades\Route;

/*
| API v1 (docs/HOP_DONG_KET_NOI.md). Giai đoạn 3c: khối giới thiệu cho web chính.
| Giai đoạn 5 thêm: GET questions, POST score, GET embed (có token).
*/

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {
    Route::get('public/intro', [EmbedController::class, 'intro'])->name('api.public.intro');
});
