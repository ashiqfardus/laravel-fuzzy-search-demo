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

        // Create articles — a fixed author pool so several articles share an author
        // (otherwise every author is used exactly once and relation search has nothing to demo)
        // and the relations-page example query ("smith") always has a match, reseed after reseed.
        $authors = collect(['John Smith', 'Jane Doe', 'Alice Johnson', 'Bob Williams', 'Charlie Brown',
            'Diana Prince', 'Edward Norton', 'Fiona Green', 'George White', 'Hannah Lee'])
            ->map(fn (string $name) => Author::firstOrCreate(['name' => $name]));
        Article::factory(30)->recycle($authors)->create();

        // Create contacts
        Contact::factory(40)->create();

        $this->command->info('Seeded: 24 users, 50 products, 30 articles, 40 contacts');
    }
}
