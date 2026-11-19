<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Làm gì:   Sao lưu database thành file nén storage/app/backups/holland-YYYYmmdd-HHiiss.sql.gz (MySQL: mysqldump; SQLite: chép file),
 *           giữ mọi bản trong backup_keep_days ngày (mặc định 14) và thêm mỗi tuần một bản trong backup_keep_weeks tuần (mặc định 8),
 *           chép thêm sang ổ BACKUP_DISK nếu có (ngoài máy chủ). Hai con số sửa ở "Cài đặt hệ thống".
 *           Mật khẩu MySQL đưa qua file cấu hình tạm, không nằm trên dòng lệnh. Không ghi dữ liệu khách vào log.
 *           Lưu ý: họ tên, email, SĐT trong file đã mã hóa bằng APP_KEY → khôi phục cần đúng APP_KEY (cất riêng, an toàn).
 * Ai gọi:   routes/console.php (mỗi đêm 02:00), hoặc gõ tay: php artisan holland:backup.
 * Dữ liệu:  toàn bộ database; config database, filesystems; cài đặt backup_keep_days; env BACKUP_DISK, BACKUP_MYSQLDUMP.
 */
#[Signature('holland:backup')]
#[Description('Sao lưu database thành file nén, xóa bản cũ, chép sang ổ ngoài nếu có')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        $connection = (string) config('database.default');
        $config = (array) config("database.connections.{$connection}");
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $file = $dir.'/holland-'.now()->format('Ymd-His').'.sql.gz';

        try {
            $raw = match ($config['driver'] ?? null) {
                'mysql', 'mariadb' => $this->mysqlDump($config),
                'sqlite' => $this->sqliteCopy($config),
                default => throw new \RuntimeException('Chưa hỗ trợ sao lưu loại database: '.($config['driver'] ?? '?')),
            };
            file_put_contents($file, gzencode($raw, 9));
            unset($raw);
        } catch (Throwable $e) {
            $this->error('Sao lưu lỗi: '.$e->getMessage());
            report(new \RuntimeException('Sao lưu database lỗi ('.class_basename($e).').'));

            return self::FAILURE;
        }

        if (filled($disk = config('holland.backup_disk'))) {
            Storage::disk($disk)->put('holland/'.basename($file), (string) file_get_contents($file));
        }

        $removed = $this->prune($dir, max(1, (int) setting('backup_keep_days', '14')), max(0, (int) setting('backup_keep_weeks', '8')));
        $this->info('Đã sao lưu: '.basename($file).' ('.round(filesize($file) / 1024).' KB). Xóa '.$removed.' bản cũ.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function mysqlDump(array $config): string
    {
        $cnf = tempnam(sys_get_temp_dir(), 'hlb');
        file_put_contents($cnf, "[client]\nuser=\"{$config['username']}\"\npassword=\"{$config['password']}\"\nhost=\"{$config['host']}\"\nport={$config['port']}\n");
        try {
            $process = new Process([
                (string) config('holland.backup_mysqldump', 'mysqldump'), '--defaults-extra-file='.$cnf,
                '--single-transaction', '--routines', '--default-character-set=utf8mb4', (string) $config['database'],
            ]);
            $process->setTimeout(1800)->mustRun();

            return $process->getOutput();
        } finally {
            @unlink($cnf);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function sqliteCopy(array $config): string
    {
        $path = (string) $config['database'];
        if ($path === ':memory:' || ! is_file($path)) {
            throw new \RuntimeException('Không tìm thấy file database SQLite.');
        }

        return (string) file_get_contents($path);
    }

    /**
     * Giữ mọi bản trong $keepDays ngày; bản cũ hơn thì mỗi tuần giữ bản mới nhất của tuần đó, trong $keepWeeks tuần.
     */
    private function prune(string $dir, int $keepDays, int $keepWeeks): int
    {
        $files = glob($dir.'/holland-*.sql.gz') ?: [];
        usort($files, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        $dailyLimit = now()->subDays($keepDays)->getTimestamp();
        $weeklyLimit = now()->subWeeks($keepWeeks)->getTimestamp();
        $weeksKept = [];
        $removed = 0;

        foreach ($files as $file) {
            $time = filemtime($file);
            if ($time >= $dailyLimit) {
                continue;
            }
            $week = date('o-W', $time);
            if ($time >= $weeklyLimit && ! isset($weeksKept[$week])) {
                $weeksKept[$week] = true;

                continue;
            }
            @unlink($file);
            $removed++;
        }

        return $removed;
    }
}
