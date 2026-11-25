<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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

        $token = 'SETUP-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
        Setting::updateOrCreate(
            ['key' => 'system.setup_token'],
            [
                'value' => $token,
                'type' => 'string',
                'group' => 'system',
                'label' => 'Single-Use Setup Token',
                'description' => 'Installation token required on Step 1 of the setup wizard.',
                'is_confirmed' => true,
            ]
        );
        Cache::forget('setting.system.setup_token');

        $this->newLine();
        $this->info('====================================================');
        $this->info(' Clean installation complete! Next step:');
        $this->info(' Navigate to: '.url('/setup'));
        $this->info(" Installation Token: {$token}");
        $this->info(' Complete the 5-step wizard to confirm placeholders.');
        $this->info('====================================================');

        return Command::SUCCESS;
    }
}
