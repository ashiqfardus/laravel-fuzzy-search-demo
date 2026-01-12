<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Article;
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

        // Create articles
        Article::factory(30)->create();

        // Create contacts
        Contact::factory(40)->create();

        $this->command->info('Seeded: 24 users, 50 products, 30 articles, 40 contacts');
    }
}
