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
        // Named users the page hints rely on — keyed on the unique users.email so a second
        // `php artisan db:seed` updates nothing and collides with nothing.
        $named = [
            ['John Doe', 'john@example.com'],
            ['Jane Smith', 'jane@example.com'],
            ['Jon Snow', 'jon.snow@example.com'],
            ['Johnny Bravo', 'johnny@example.com'],
            ['Violet VonRueden', 'violet@example.com'],
        ];

        foreach ($named as [$name, $email]) {
            User::firstOrCreate(
                ['email' => $email],
                User::factory()->raw(['name' => $name, 'email' => $email])
            );
        }

        // Top-ups are "fill to N", never "add N": a reseed creates zero random rows, so the
        // factories' unique() pools (users.email, contacts.email, products.sku) can never
        // collide with rows that are already in the database.
        User::factory(max(0, 25 - User::count()))->create();

        $this->seedFixedProducts();

        // Create articles — a fixed author pool so several articles share an author
        // (otherwise every author is used exactly once and relation search has nothing to demo)
        // and the relations-page example query ("smith") always has a match, reseed after reseed.
        $authors = collect(['John Smith', 'Jane Doe', 'Alice Johnson', 'Bob Williams', 'Charlie Brown',
            'Diana Prince', 'Edward Norton', 'Fiona Green', 'George White', 'Hannah Lee'])
            ->map(fn (string $name) => Author::firstOrCreate(['name' => $name]));
        Article::factory(max(0, 30 - Article::count()))->recycle($authors)->create();

        Contact::factory(max(0, 40 - Contact::count()))->create();

        $this->command->info(sprintf(
            'Seeded: %d users, %d products, %d articles, %d contacts',
            User::count(), Product::count(), Article::count(), Contact::count()
        ));
    }

    /**
     * Every fixed catalog entry exists at least once (firstOrCreate, so a reseed never
     * duplicates them or crashes on the unique sku), each one carries the catalog's real
     * description, then the row count is topped up to 50 with random draws from the same
     * catalog so every "try: iphne, macbok…" hint always matches, reseed after reseed.
     *
     * A separate method (not inlined in run()) so it can be re-run in isolation, e.g. to
     * backfill a missing catalog entry on a live database. run() as a whole is re-runnable too.
     */
    public function seedFixedProducts(): void
    {
        foreach (ProductFactory::catalog() as $entry) {
            $product = Product::firstOrCreate(['name' => $entry['name']], [
                'description' => $entry['description'] ?? fake()->paragraph(3),
                'sku' => strtoupper(fake()->unique()->bothify('???-#####')),
                'brand' => $entry['brand'],
                'category_id' => $entry['category_id'],
                'price' => fake()->randomFloat(2, 29.99, 2999.99),
            ]);

            // A catalog row seeded before the real descriptions existed still holds Faker
            // lorem; overwrite just that column (the save reindexes through the observer).
            if (isset($entry['description']) && $product->description !== $entry['description']) {
                $product->update(['description' => $entry['description']]);
            }
        }
        Product::factory(max(0, 50 - Product::count()))->create();
    }
}
