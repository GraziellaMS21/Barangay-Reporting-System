<?php

namespace App\Console\Commands;

use Database\Seeders\RequiredDataSeeder;
use Illuminate\Console\Command;

class AppEmptyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:empty {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe database and seed only required baseline data with unconfirmed placeholders for a fresh setup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Wiping database and preparing clean setup environment...');

        $this->call('migrate:fresh', [
            '--force' => $this->option('force'),
        ]);

        $this->info('Seeding required baseline data...');
        $this->call('db:seed', [
            '--class' => RequiredDataSeeder::class,
            '--force' => $this->option('force'),
        ]);

        $this->newLine();
        $this->info('====================================================');
        $this->info(' Clean installation complete! Next step:');
        $this->info(' Navigate to: '.url('/setup'));
        $this->info(' Complete the 5-step wizard to confirm placeholders.');
        $this->info('====================================================');

        return Command::SUCCESS;
    }
}
