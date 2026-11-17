<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;
use ZipArchive;

class BackupFiles extends Command
{
    /**
     * Top-level directories that are never backed up, regardless of .gitignore.
     */
    private const EXCLUDED_ROOT_DIRECTORIES = ['storage', 'vendor', 'node_modules'];

    private const BACKUP_DIRECTORY = 'app_backups';

    protected $signature = 'files:backup
                            {--disk=s3}
                            {--path= : Directory to back up (defaults to the application root)}
                            {--filename=}
                            {--days=14}
                            {--keep-monthly}
                            {--no-prune}
                            {--prune-only}';

    protected $description = 'Zip application files (respecting .gitignore) and upload to S3';

    public function handle(): int
    {
        $disk = (string) ($this->option('disk') ?: 's3');

        if ($this->option('prune-only')) {
            $this->pruneBackups($disk);

            return Command::SUCCESS;
        }

        $basePath = (string) ($this->option('path') ?: base_path());
        if (!is_dir($basePath)) {
            $this->error("Backup path does not exist: {$basePath}");

            return Command::FAILURE;
        }

        // Kept outside the project so the archive can never include itself.
        $tempZip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'files_backup_' . uniqid('', true) . '.zip';

        try {
            $fileCount = $this->createArchive($basePath, $tempZip);

            $fileName = $this->option('filename') ?: sprintf('backup_files_%s.zip', now()->format('Y-m-d_His'));
            $targetPath = self::BACKUP_DIRECTORY . "/{$fileName}";

            $stream = fopen($tempZip, 'rb');
            if ($stream === false) {
                $this->error("Unable to open backup file for reading: {$tempZip}");

                return Command::FAILURE;
            }

            $uploaded = Storage::disk($disk)->put($targetPath, $stream, 'private');
            if (is_resource($stream)) {
                fclose($stream);
            }

            if (!$uploaded) {
                $this->error("Failed to upload backup to disk [{$disk}] at [{$targetPath}].");
                Log::error("Failed to upload files backup to disk [{$disk}] at [{$targetPath}].");

                return Command::FAILURE;
            }

            $sizeMb = round(filesize($tempZip) / 1024 / 1024, 2);
            $this->info("Backup stored successfully at [{$disk}]: {$targetPath} ({$fileCount} files, {$sizeMb} MB)");
            Log::info("Files backup stored successfully at [{$disk}]: {$targetPath} ({$fileCount} files, {$sizeMb} MB)");

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            Log::error("Files backup exception: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            return Command::FAILURE;
        } finally {
            if (file_exists($tempZip)) {
                @unlink($tempZip);
            }
        }
    }

    private function createArchive(string $basePath, string $destination): int
    {
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Unable to create zip archive: {$destination}");
        }

        $count = 0;

        foreach ($this->filesToBackup($basePath) as $file) {
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());

            if (!$zip->addFile($file->getPathname(), $relativePath)) {
                throw new RuntimeException("Unable to add file to archive: {$relativePath}");
            }

            $count++;
        }

        if ($count === 0) {
            $zip->unchangeAll();
            throw new RuntimeException("No files found to back up in: {$basePath}");
        }

        // The archive is only written to disk here, so this is where read errors surface.
        if (!$zip->close()) {
            throw new RuntimeException('Unable to finalize zip archive: ' . $zip->getStatusString());
        }

        return $count;
    }

    private function filesToBackup(string $basePath): Finder
    {
        return (new Finder())
            ->files()
            ->in($basePath)
            ->ignoreDotFiles(false)
            ->ignoreVCS(true)
            ->ignoreVCSIgnored(true)
            // Anchored to the root: Finder::exclude('vendor') would also drop nested
            // directories such as resources/views/vendor.
            ->filter(
                fn (SplFileInfo $file): bool => !in_array($file->getRelativePathname(), self::EXCLUDED_ROOT_DIRECTORIES, true),
                true
            );
    }
}
