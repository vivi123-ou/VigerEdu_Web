<?php

use App\Jobs\SendContactToCrm;
use App\Models\ContactMessage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Gửi lại sang CRM các form liên hệ chưa đồng bộ (CRM lỗi lâu, hết lượt thử của hàng đợi).
| Chạy tay: php artisan crm:resend-contacts — hoặc để scheduler chạy mỗi giờ.
*/
Artisan::command('crm:resend-contacts {--days=30 : Chỉ gửi lại form trong N ngày gần nhất}', function () {
    if (! config('services.crm.url') || ! config('services.crm.token')) {
        $this->warn('Chưa cấu hình CRM_API_URL / CRM_API_TOKEN — bỏ qua.');

        return;
    }

    $count = 0;
    ContactMessage::whereNull('crm_synced_at')
        ->whereNotNull('consent_text')
        ->where('created_at', '>=', now()->subDays((int) $this->option('days')))
        ->each(function ($message) use (&$count) {
            SendContactToCrm::dispatch($message);
            $count++;
        });

    $this->info("Đã đưa {$count} form liên hệ vào hàng đợi gửi CRM.");
})->purpose('Gửi lại sang CRM các form liên hệ chưa đồng bộ');

Schedule::command('crm:resend-contacts')->hourly()->withoutOverlapping();
