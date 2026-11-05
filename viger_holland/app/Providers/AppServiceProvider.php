<?php

namespace App\Providers;

use App\Catalogs\JsonMajorCatalog;
use App\Contracts\MajorCatalog;
use App\Contracts\ScoringEngine;
use App\Data\ScoringConfig;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ScoringConfig::class, fn () => ScoringConfig::fromArray(config('holland.scoring', [])));

        // Chọn engine theo cấu hình: đổi thuật toán không cần sửa code nơi gọi.
        $this->app->bind(ScoringEngine::class, function (Application $app): ScoringEngine {
            $name = (string) config('holland.engine');
            $class = config("holland.engines.{$name}");

            if (! is_string($class) || ! is_subclass_of($class, ScoringEngine::class)) {
                throw new InvalidArgumentException("Không tìm thấy thuật toán chấm điểm '{$name}' (xem config/holland.php → engines).");
            }

            return $app->make($class);
        });

        // Giai đoạn 4: thay bằng CrmMajorCatalog (gọi CRM, lỗi thì quay về file này).
        $this->app->singleton(MajorCatalog::class, fn () => new JsonMajorCatalog(config('holland.majors_json')));
    }

    public function boot(): void
    {
        // An toàn dữ liệu: chặn migrate:fresh / migrate:reset / db:wipe khi chạy thật.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Form công khai: tối đa 5 lần nộp bài / phút / địa chỉ IP, báo lỗi tiếng Việt.
        RateLimiter::for('submit-test', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn () => back()->withInput()->withErrors([
                'submit' => 'Bạn vừa gửi quá nhiều lần. Vui lòng đợi 1 phút rồi bấm "Xem kết quả" lại.',
            ])));
    }
}
