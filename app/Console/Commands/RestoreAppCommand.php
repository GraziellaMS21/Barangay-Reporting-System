<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class RestoreAppCommand extends Command
{
    protected $signature = 'app:restore {file : The backup zip filename or path} {--force : Force restoration without confirmation}';

    protected $description = 'Restore system database and private storage from a backup zip archive';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $backupDir = storage_path('app/private/backups');

        $fullPath = File::exists($file) ? $file : "{$backupDir}/{$file}";

        if (! File::exists($fullPath)) {
            $this->error("Backup archive file not found: {$fullPath}");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Restoring will overwrite current database and files. Proceed?')) {
            $this->warn('Restoration cancelled.');

            return self::SUCCESS;
        }

        $zip = new ZipArchive;
        if ($zip->open($fullPath) !== true) {
            $this->error('Failed to open zip archive.');

            return self::FAILURE;
        }

        $sqliteDestination = database_path('database.sqlite');
        $sqliteExtracted = $zip->getFromName('database/database.sqlite');

        if ($sqliteExtracted !== false) {
            File::put($sqliteDestination, $sqliteExtracted);
            $this->info('Restored database/database.sqlite successfully.');
        }

        $zip->close();
        $this->info("System restored successfully from {$fullPath}.");

        return self::SUCCESS;
    }
}
