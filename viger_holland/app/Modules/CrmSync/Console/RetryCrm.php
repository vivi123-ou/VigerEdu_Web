<?php

namespace App\Modules\CrmSync\Console;

use App\Modules\CrmSync\Jobs\SendToCrm;
use App\Modules\CrmSync\Models\CrmOutbox;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Làm gì:   Gửi lại các gói CRM đã tới giờ gửi lại (trạng thái "Chờ gửi", next_try_at đã qua). Chạy theo lịch mỗi 5 phút.
 * Ai gọi:   routes/console.php (Schedule), hoặc gõ tay: php artisan crm:retry.
 * Dữ liệu:  crm_outbox.
 */
#[Signature('crm:retry')]
#[Description('Gửi lại các gói sang CRM đã tới giờ gửi lại')]
class RetryCrm extends Command
{
    public function handle(): int
    {
        $ids = CrmOutbox::query()->where('status', CrmOutbox::PENDING)
            ->where(fn ($q) => $q->whereNull('next_try_at')->orWhere('next_try_at', '<=', now()))
            ->orderBy('id')->limit(200)->pluck('id');

        $ids->each(fn (int $id) => SendToCrm::dispatch($id));
        $this->info('Đã đưa '.$ids->count().' gói vào hàng đợi gửi.');

        return self::SUCCESS;
    }
}
