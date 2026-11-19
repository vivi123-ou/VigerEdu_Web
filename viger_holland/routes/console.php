<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Gửi lại các gói sang CRM bị lỗi tạm thời (mất mạng, CRM bảo trì…), mỗi 5 phút. Cần chạy php artisan schedule:work (máy chạy thử) hoặc cron trên máy chủ.
Schedule::command('crm:retry')->everyFiveMinutes()->withoutOverlapping();

// Sao lưu database mỗi đêm (giữ backup_keep_days ngày). Máy chủ cần cron: * * * * * php artisan schedule:run
Schedule::command('holland:backup')->dailyAt('02:00')->withoutOverlapping();

// Dọn nhật ký cũ (lịch sử thao tác, hành vi, gói CRM đã gửi, mã hết hạn, file log) theo "Cài đặt hệ thống". Không đụng dữ liệu khách.
Schedule::command('holland:prune')->dailyAt('03:00')->withoutOverlapping();

// Kiểm tra lỗ hổng bảo mật của các package PHP (composer audit), sáng thứ Hai; có lỗ hổng thì ghi Lịch sử thao tác + log.
Schedule::command('holland:security-audit')->weeklyOn(1, '04:00')->withoutOverlapping();
