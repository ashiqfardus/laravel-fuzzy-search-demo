<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Article;
use App\Models\Author;
use App\Models\Contact;
use Database\Factories\ProductFactory;
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

        $this->seedFixedProducts();

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

    /**
     * Every fixed catalog entry exists at least once (firstOrCreate, so a reseed never
     * duplicates them or crashes on the unique sku), then top up to 50 with random draws from
     * the same catalog so every "try: iphne, macbok…" hint always matches, reseed after reseed
     * — same pattern as the fixed author pool in run() above.
     *
     * A separate method (not inlined in run()) so it can be re-run in isolation, e.g. to
     * backfill a missing catalog entry on a live DB — run() as a whole is not safe to repeat
     * (users.email is unique and the named users would collide).
     */
    public function seedFixedProducts(): void
    {
        foreach (ProductFactory::catalog() as $entry) {
            Product::firstOrCreate(['name' => $entry['name']], [
                'description' => $entry['description'] ?? fake()->paragraph(3),
                'sku' => strtoupper(fake()->unique()->bothify('???-#####')),
                'brand' => $entry['brand'],
                'category_id' => $entry['category_id'],
                'price' => fake()->randomFloat(2, 29.99, 2999.99),
            ]);
        }
        Product::factory(max(0, 50 - Product::count()))->create();
    }
}
