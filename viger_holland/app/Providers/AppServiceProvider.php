<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
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
    }
}
