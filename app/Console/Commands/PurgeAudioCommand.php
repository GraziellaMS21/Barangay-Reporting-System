<?php

namespace App\Console\Commands;

use App\Services\Voice\VoiceService;
use Illuminate\Console\Command;

class PurgeAudioCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:purge-audio';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge expired audio recordings and orphaned draft audio files from private disk.';

    /**
     * Execute the console command.
     */
    public function handle(VoiceService $voiceService): int
    {
        $this->info('Starting audio retention purge...');

        $count = $voiceService->purgeExpired();

        $this->info("Purged {$count} expired/orphaned audio file(s).");

        return self::SUCCESS;
    }
}
