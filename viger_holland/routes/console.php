<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Gửi lại các gói sang CRM bị lỗi tạm thời (mất mạng, CRM bảo trì…), mỗi 5 phút. Cần chạy php artisan schedule:work (máy chạy thử) hoặc cron trên máy chủ.
Schedule::command('crm:retry')->everyFiveMinutes()->withoutOverlapping();
