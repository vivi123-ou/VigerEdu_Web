<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // An toàn dữ liệu: chặn migrate:fresh / migrate:reset / db:wipe khi chạy thật.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // API: tối đa 60 lần / phút / địa chỉ IP.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        // Form công khai: tối đa 5 lần gửi / phút / địa chỉ IP, báo lỗi tiếng Việt.
        RateLimiter::for('submit-test', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn () => back()->withInput()->withErrors([
                'submit' => 'Bạn vừa gửi quá nhiều lần. Vui lòng đợi 1 phút rồi thử lại.',
            ])));
    }
}
