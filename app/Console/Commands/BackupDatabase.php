<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\ExecutableFinder;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup
                            {--disk=s3}
                            {--database=}
                            {--connection=}
                            {--filename=}
                            {--days=14}
                            {--keep-monthly}
                            {--no-compression}
                            {--no-prune}
                            {--prune-only}';

    protected $description = 'Dump MySQL database using mysqldump and upload to S3';

    public function handle(): int
    {
        $disk = (string) ($this->option('disk') ?: 's3');
        $connectionName = $this->option('connection') ?: config('database.default');
        $dbConfig = config("database.connections.{$connectionName}");

        if (!is_array($dbConfig) || ($dbConfig['driver'] ?? '') !== 'mysql') {
            $this->error('The backup command only supports MySQL connections.');
            return Command::FAILURE;
        }

        $database = (string) ($this->option('database') ?: ($dbConfig['database'] ?? ''));
        if (empty($database)) {
            $this->error('No database specified for backup.');
            return Command::FAILURE;
        }

        if ($this->option('prune-only')) {
            $this->pruneBackups($disk, $database);
            return Command::SUCCESS;
        }

        $finder = new ExecutableFinder();
        $binary = env('MYSQLDUMP_PATH') ?: $finder->find('mysqldump', 'mysqldump');

        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = (string) ($dbConfig['port'] ?? '3306');
        $username = $dbConfig['username'] ?? 'root';
        $password = $dbConfig['password'] ?? '';
        $socket = $dbConfig['unix_socket'] ?? '';
        $charset = $dbConfig['charset'] ?? 'utf8mb4';

        $tempDir = sys_get_temp_dir();
        $tempCnf = tempnam($tempDir, 'cnf_');
        chmod($tempCnf, 0600);

        $cnfLines = [
            '[client]',
            'host=' . $host,
            'port=' . $port,
            'user=' . addcslashes($username, "\\\n\r\t"),
            'default-character-set=' . $charset,
        ];

        if ($password !== '') {
            $cnfLines[] = 'password=' . addcslashes($password, "\\\n\r\t");
        }

        if (!empty($socket)) {
            $cnfLines[] = 'socket=' . $socket;
        }

        file_put_contents($tempCnf, implode("\n", $cnfLines) . "\n");

        $tempSql = $tempDir . DIRECTORY_SEPARATOR . 'dump_' . uniqid('', true) . '.sql';
        $tempGz = null;

        try {
            $cmd = [
                $binary,
                "--defaults-extra-file={$tempCnf}",
                '--single-transaction',
                '--quick',
                '--skip-lock-tables',
                '--routines',
                '--triggers',
                "--result-file={$tempSql}",
                $database,
            ];

            $result = Process::forever()->run($cmd);

            if (!$result->successful()) {
                $errorMsg = $result->errorOutput() ?: $result->output();
                $this->error("mysqldump failed: {$errorMsg}");
                Log::error("Database backup failed: {$errorMsg}");
                return Command::FAILURE;
            }

            if (!file_exists($tempSql) || filesize($tempSql) === 0) {
                $this->error('Dump file was not created or is empty.');
                return Command::FAILURE;
            }

            $useCompression = !$this->option('no-compression') && function_exists('gzopen');
            $uploadFile = $tempSql;
            $extension = 'sql';

            if ($useCompression) {
                $tempGz = $tempSql . '.gz';
                $this->compressGzip($tempSql, $tempGz);
                $uploadFile = $tempGz;
                $extension = 'sql.gz';
            }

            $fileName = $this->option('filename') ?: sprintf(
                'backup_%s_%s.%s',
                $database,
                now()->format('Y-m-d_His'),
                $extension
            );

            $targetPath = "db_backups/{$fileName}";

            $stream = fopen($uploadFile, 'rb');
            if ($stream === false) {
                $this->error("Unable to open backup file for reading: {$uploadFile}");
                return Command::FAILURE;
            }

            $uploaded = Storage::disk($disk)->put($targetPath, $stream, 'private');
            if (is_resource($stream)) {
                fclose($stream);
            }

            if (!$uploaded) {
                $this->error("Failed to upload backup to disk [{$disk}] at [{$targetPath}].");
                Log::error("Failed to upload database backup to disk [{$disk}] at [{$targetPath}].");
                return Command::FAILURE;
            }

            $sizeMb = round(filesize($uploadFile) / 1024 / 1024, 2);
            $this->info("Backup stored successfully at [{$disk}]: {$targetPath} ({$sizeMb} MB)");
            Log::info("Database backup stored successfully at [{$disk}]: {$targetPath} ({$sizeMb} MB)");

            if (!$this->option('no-prune')) {
                $this->pruneBackups($disk, $database);
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            Log::error("Database backup exception: {$e->getMessage()}", [
                'exception' => $e,
            ]);
            return Command::FAILURE;
        } finally {
            if (file_exists($tempCnf)) {
                @unlink($tempCnf);
            }
            if (file_exists($tempSql)) {
                @unlink($tempSql);
            }
            if ($tempGz && file_exists($tempGz)) {
                @unlink($tempGz);
            }
        }
    }

    private function pruneBackups(string $disk, string $database): void
    {
        try {
            $allFiles = Storage::disk($disk)->files('db_backups');
        } catch (Throwable $e) {
            $this->warn("Failed to list files for pruning: {$e->getMessage()}");
            return;
        }

        $backupFiles = [];
        $today = now()->startOfDay();
        $daysLimit = (int) ($this->option('days') ?: 14);
        $pattern = '/^backup_' . preg_quote($database, '/') . '_(\d{4}-\d{2}-\d{2})(?:_(\d{6}))?\.(sql|sql\.gz|gz)$/';

        foreach ($allFiles as $file) {
            $baseName = basename($file);
            if (!preg_match($pattern, $baseName, $matches)) {
                continue;
            }

            $date = rescue(fn () => !empty($matches[2])
                ? Carbon::createFromFormat('Y-m-d_His', "{$matches[1]}_{$matches[2]}")
                : Carbon::parse($matches[1])->startOfDay(), null, false)
                ?: rescue(fn () => Carbon::createFromTimestamp(Storage::disk($disk)->lastModified($file)), now(), false);

            $backupFiles[] = [
                'path' => $file,
                'date' => $date,
                'dayKey' => $date->format('Y-m-d'),
                'daysAgo' => (int) round($date->copy()->startOfDay()->diffInDays($today, false)),
            ];
        }

        if (empty($backupFiles)) {
            return;
        }

        $dailyBuckets = [];
        $monthlyCandidates = [];

        foreach ($backupFiles as $item) {
            $days = $item['daysAgo'];
            if ($days <= $daysLimit) {
                $dailyBuckets[$item['dayKey']][] = $item;
            } elseif ($this->option('keep-monthly') && $days <= 30) {
                $monthlyCandidates[] = $item;
            }
        }

        $keepPaths = [];

        foreach ($dailyBuckets as $group) {
            usort($group, fn ($a, $b) => $b['date']->timestamp <=> $a['date']->timestamp);
            $keepPaths[] = $group[0]['path'];
        }

        if ($this->option('keep-monthly') && !empty($monthlyCandidates)) {
            usort($monthlyCandidates, fn ($a, $b) => $a['date']->timestamp <=> $b['date']->timestamp);
            $keepPaths[] = $monthlyCandidates[0]['path'];
        }

        $deletedCount = 0;
        foreach ($backupFiles as $item) {
            if (!in_array($item['path'], $keepPaths, true)) {
                try {
                    Storage::disk($disk)->delete($item['path']);
                    Log::info("Pruned old backup from {$disk}: {$item['path']}");
                    $deletedCount++;
                } catch (Throwable $e) {
                    $this->warn("Failed to delete {$item['path']}: {$e->getMessage()}");
                }
            }
        }

        if ($deletedCount > 0) {
            $this->info("Pruned {$deletedCount} old backup(s) according to retention policy.");
        }
    }

    private function compressGzip(string $source, string $destination): void
    {
        $fpIn = fopen($source, 'rb');
        $fpOut = gzopen($destination, 'wb6');

        while (!feof($fpIn)) {
            gzwrite($fpOut, fread($fpIn, 1024 * 512));
        }

        fclose($fpIn);
        gzclose($fpOut);
    }
}
