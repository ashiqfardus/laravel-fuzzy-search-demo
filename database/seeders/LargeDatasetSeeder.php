<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Article;
use App\Models\Contact;
use Illuminate\Database\Seeder;

class LargeDatasetSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding 10k rows (large dataset)...');
        User::factory(2500)->create();
        Product::factory(2500)->create();
        Article::factory(2500)->create();
        Contact::factory(2500)->create();
        $this->command->info('Done. 10,000 rows seeded across 4 models.');
    }
}
