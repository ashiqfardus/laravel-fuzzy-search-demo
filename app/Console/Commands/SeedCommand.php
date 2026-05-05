<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedCommand extends Command
{
    protected $signature   = 'demo:seed {--huge : Seed 1 million users (takes ~10 minutes on commodity hardware)}';
    protected $description = 'Seed the demo database';

    public function handle(): int
    {
        if ($this->option('huge') && app()->environment('production')) {
            $this->error('--huge is disabled in production (APP_ENV=production).');
            return self::FAILURE;
        }

        if ($this->option('huge')) {
            $this->warn('Seeding 1M users. This will take several minutes...');
            $this->call('db:seed', ['--class' => \Database\Seeders\HugeDatasetSeeder::class, '--force' => true]);
        } else {
            $this->info('Seeding standard demo dataset (~25 users, 50 products, 30 articles, 40 contacts)...');
            $this->call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class, '--force' => true]);
        }

        return self::SUCCESS;
    }
}
