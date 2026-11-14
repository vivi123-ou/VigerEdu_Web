<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $supportedLocales = config('app.supported_locales');

        // Nội dung chưa dịch (vd. bài viết chưa có bản tiếng Anh) => hiển thị bản tiếng Việt,
        // nếu cũng không có thì lấy bất kỳ bản dịch nào đang có.
        Translatable::fallback(fallbackLocale: $supportedLocales[0], fallbackAny: true);
        Translatable::locales($supportedLocales);
    }
}
