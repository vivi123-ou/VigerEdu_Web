<?php

namespace App\Modules\System\Console;

use App\Models\AdminActivity;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Làm gì:   Kiểm tra lỗ hổng bảo mật đã công bố của các package PHP đang dùng (composer audit), mỗi tuần.
 *           Có lỗ hổng → ghi "Lịch sử thao tác" + log cảnh báo để IT cập nhật (composer update tên-gói) và chạy test.
 *           Máy không có composer thì chỉ báo, không lỗi.
 * Ai gọi:   routes/console.php (thứ Hai 04:00), hoặc gõ tay: php artisan holland:security-audit.
 * Dữ liệu:  composer.lock; env COMPOSER_BIN (mặc định "composer").
 */
#[Signature('holland:security-audit')]
#[Description('Kiểm tra lỗ hổng bảo mật của các package (composer audit), ghi kết quả vào lịch sử')]
class SecurityAudit extends Command
{
    public function handle(): int
    {
        try {
            $process = new Process([(string) config('security.composer_bin', 'composer'), 'audit', '--format=json', '--no-interaction', '--locked'], base_path());
            $process->setTimeout(300)->run();
            $report = json_decode($process->getOutput(), true);
        } catch (Throwable) {
            $report = null;
        }

        if (! is_array($report)) {
            $this->warn('Không chạy được composer audit trên máy này (thiếu composer?). Bỏ qua.');

            return self::SUCCESS;
        }

        $packages = array_keys(array_filter((array) ($report['advisories'] ?? [])));
        if ($packages === []) {
            $this->info('Không có package nào có lỗ hổng đã công bố.');
            AdminActivity::record('audit', 'security.packages', null, 'Kiểm tra lỗ hổng package: không có lỗ hổng');

            return self::SUCCESS;
        }

        $summary = count($packages).' package có lỗ hổng: '.implode(', ', $packages).'. IT cần cập nhật (composer update) và chạy test.';
        $this->error($summary);
        AdminActivity::record('audit', 'security.packages', null, 'Kiểm tra lỗ hổng package: '.$summary);
        Log::warning('composer audit: có lỗ hổng', ['packages' => $packages]);

        return self::FAILURE;
    }
}
