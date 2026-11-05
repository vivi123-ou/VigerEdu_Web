<?php

namespace App\Providers;

use App\Catalogs\JsonMajorCatalog;
use App\Contracts\MajorCatalog;
use App\Contracts\ScoringEngine;
use App\Data\ScoringConfig;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
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
    }
}
