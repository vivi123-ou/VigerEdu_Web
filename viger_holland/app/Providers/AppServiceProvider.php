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

        // Trang khách: mỗi việc một bộ đếm riêng theo địa chỉ IP (viết "throttle:5,1" thì mọi route dùng CHUNG một bộ đếm,
        // khách làm xong 6 trang bài sẽ bị chặn ở form). Quá giới hạn thì quay lại trang trước, báo lỗi tiếng Việt.
        $tooMany = fn (string $field) => fn () => back()->withInput()->withErrors([
            $field => setting('throttle_error', 'Bạn vừa gửi quá nhiều lần. Vui lòng đợi 1 phút rồi thử lại.'),
        ]);
        RateLimiter::for('quiz-start', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('quiz-page', fn (Request $request) => Limit::perMinute(30)->by($request->ip())->response($tooMany('answers')));
        RateLimiter::for('report-form', fn (Request $request) => Limit::perMinute(5)->by($request->ip())->response($tooMany('form')));
        RateLimiter::for('report-code', fn (Request $request) => Limit::perMinute(10)->by($request->ip())->response($tooMany('code')));
        RateLimiter::for('report-resend', fn (Request $request) => Limit::perMinute(5)->by($request->ip())->response($tooMany('code')));
    }
}
