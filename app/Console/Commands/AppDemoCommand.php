<?php

namespace App\Console\Commands;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

class AppDemoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:demo {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe database and seed rich demo dataset for testing, demos, and evaluation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Resetting database and seeding full demo dataset...');

        $this->call('migrate:fresh', [
            '--force' => $this->option('force'),
        ]);

        $this->info('Seeding baseline and existing demo data...');
        $this->call('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--force' => $this->option('force'),
        ]);

        $this->info('Seeding Phase 0 demo cases, personnel, and portal accounts...');
        $this->call('db:seed', [
            '--class' => DemoDataSeeder::class,
            '--force' => $this->option('force'),
        ]);

        $this->newLine();
        $this->info('================================================================');
        $this->info(' Demo environment is ready! Test credentials:');
        $this->info(' ---------------------------------------------------------------');
        $this->info(' Admin:    admin@calarian.gov.ph   | Password: password123');
        $this->info(' Staff:    staff@calarian.gov.ph   | Password: password123 (full)');
        $this->info(' Encoder:  clerk@calarian.gov.ph   | Password: password123 (cases)');
        $this->info(' Agency:   cswdo@zamboanga.gov.ph  | Password: password123 (CSWDO)');
        $this->info(' ---------------------------------------------------------------');
        $this->info(' URL:      '.url('/login'));
        $this->info(' UI Guide: '.url('/admin/_ui'));
        $this->info('================================================================');

        return Command::SUCCESS;
    }
}
