<?php

namespace App\Modules\System\Console;

use App\Models\AdminActivity;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Làm gì:   Dọn dữ liệu nhật ký cũ để không tràn ổ (ý Thầy 18/11): lịch sử thao tác, hành vi khách, gói CRM đã gửi xong,
 *           mã email hết hạn, file log lỗi của phần mềm. Số ngày / tháng giữ lấy ở "Cài đặt hệ thống".
 *           KHÔNG đụng dữ liệu khách, kết quả làm bài, gói CRM đang chờ hoặc lỗi. Ghi một dòng lịch sử "Hệ thống tự dọn".
 * Ai gọi:   routes/console.php (mỗi đêm 03:00), hoặc gõ tay: php artisan holland:prune (--dry-run để chỉ đếm).
 * Dữ liệu:  admin_activities, tracking_events, crm_outbox, otp_codes, storage/logs/*.log.
 */
#[Signature('holland:prune {--dry-run : Chỉ đếm, không xóa}')]
#[Description('Dọn nhật ký cũ theo Cài đặt hệ thống (lịch sử thao tác, hành vi, gói CRM đã gửi, mã hết hạn, file log)')]
class PruneOldRecords extends Command
{
    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $months = fn (string $key, int $default) => now()->subMonths(max(1, (int) setting($key, (string) $default)));

        $targets = [
            'Lịch sử thao tác' => DB::table('admin_activities')->where('created_at', '<', $months('logs_keep_months', 12)),
            'Hành vi khách' => DB::table('tracking_events')->where('occurred_at', '<', $months('tracking_keep_months', 12)),
            'Gói CRM đã gửi' => DB::table('crm_outbox')->where('status', 'sent')
                ->where('sent_at', '<', now()->subDays(max(7, (int) setting('crm_sent_keep_days', '90')))),
            'Mã email hết hạn' => DB::table('otp_codes')->where('expires_at', '<', now()->subDay()),
        ];

        $counts = [];
        foreach ($targets as $label => $query) {
            $counts[$label] = $dry ? $query->count() : $query->delete();
        }
        $counts['File log lỗi'] = $this->pruneLogFiles(max(1, (int) setting('app_log_keep_days', '30')), $dry);

        $summary = collect($counts)->map(fn (int $count, string $label) => "{$label}: {$count}")->implode(', ');
        $this->info(($dry ? 'Sẽ xóa' : 'Đã xóa').' – '.$summary);

        if (! $dry && array_sum($counts) > 0) {
            AdminActivity::record(AdminActivity::ACTION_DELETE, AdminActivity::SUBJECT_SETTINGS.'.system', null, 'Hệ thống tự dọn nhật ký cũ – '.$summary);
        }

        return self::SUCCESS;
    }

    private function pruneLogFiles(int $keepDays, bool $dry): int
    {
        $removed = 0;
        foreach (glob(storage_path('logs/*.log')) ?: [] as $file) {
            if (filemtime($file) < now()->subDays($keepDays)->getTimestamp()) {
                if (! $dry) {
                    @unlink($file);
                }
                $removed++;
            }
        }

        return $removed;
    }
}
