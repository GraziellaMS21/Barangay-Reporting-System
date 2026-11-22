<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupAppCommand extends Command
{
    protected $signature = 'app:backup {--retention=14 : Days to retain backup files}';

    protected $description = 'Archive database and storage files to private disk with automated retention pruning';

    public function handle(): int
    {
        $this->info('Starting system backup...');

        $backupDir = storage_path('app/private/backups');
        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y_m_d_His');
        $fileName = "backup_{$timestamp}.zip";
        $zipPath = "{$backupDir}/{$fileName}";

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('Failed to create zip archive.');

            return self::FAILURE;
        }

        // 1. Database
        $sqlitePath = database_path('database.sqlite');
        if (File::exists($sqlitePath)) {
            $zip->addFile($sqlitePath, 'database/database.sqlite');
            $this->line('  - Archived SQLite database.');
        } else {
            // Write database metadata
            $zip->addFromString('database/connection_info.txt', 'DB_CONNECTION='.config('database.default'));
        }

        // 2. Private Storage (excluding backups)
        $privateDir = storage_path('app/private');
        if (File::isDirectory($privateDir)) {
            $files = File::allFiles($privateDir);
            foreach ($files as $file) {
                if (str_starts_with($file->getRelativePathname(), 'backups')) {
                    continue;
                }
                $zip->addFile($file->getPathname(), 'storage/private/'.$file->getRelativePathname());
            }
            $this->line('  - Archived private storage.');
        }

        // 3. Metadata
        $metadata = [
            'created_at' => now()->toIso8601String(),
            'app_version' => '1.0.0',
            'environment' => app()->environment(),
        ];
        $zip->addFromString('metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));

        $zip->close();
        $fileSizeMb = round(File::size($zipPath) / 1024 / 1024, 2);
        $this->info("Backup successfully generated: {$fileName} ({$fileSizeMb} MB)");

        // 4. Prune backups older than retention days
        $retentionDays = (int) $this->option('retention');
        $this->pruneOldBackups($backupDir, $retentionDays);

        return self::SUCCESS;
    }

    protected function pruneOldBackups(string $backupDir, int $days): void
    {
        $files = File::files($backupDir);
        $cutoff = now()->subDays($days);
        $deleted = 0;

        foreach ($files as $file) {
            if ($file->getExtension() === 'zip') {
                $lastModified = Carbon::createFromTimestamp($file->getMTime());
                if ($lastModified->lt($cutoff)) {
                    File::delete($file->getPathname());
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            $this->info("Pruned {$deleted} backup file(s) older than {$days} days.");
        }
    }
}
