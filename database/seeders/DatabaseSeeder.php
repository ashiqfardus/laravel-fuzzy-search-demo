<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Article;
use App\Models\Author;
use App\Models\Contact;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create users
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);
        User::factory()->create([
            'name' => 'Jon Snow',
            'email' => 'jon.snow@example.com',
        ]);
        User::factory()->create([
            'name' => 'Johnny Bravo',
            'email' => 'johnny@example.com',
        ]);
        User::factory()->create([
            'name' => 'Violet VonRueden',
            'email' => 'violet@example.com',
        ]);
        User::factory(20)->create();

        // Create products
        Product::factory(50)->create();

        // Create articles — a small author pool so several articles share an author
        // (otherwise every author is used exactly once and relation search has nothing to demo).
        $authors = Author::factory()->count(10)->create();
        Article::factory(30)->recycle($authors)->create();

        // Create contacts
        Contact::factory(40)->create();

        $this->command->info('Seeded: 24 users, 50 products, 30 articles, 40 contacts');
    }
}
