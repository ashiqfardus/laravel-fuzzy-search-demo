<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LargeDatasetSeeder extends Seeder
{
    private const ROWS_PER_MODEL = 100_000;
    private const CHUNK_SIZE     = 1_000;

    public function run(): void
    {
        $this->command->info('Seeding 100k rows per model (400k total) using bulk inserts...');

        $this->seedUsers();
        $this->seedProducts();
        $this->seedArticles();
        $this->seedContacts();

        $this->command->info('Done.');
        $this->command->table(
            ['Model', 'Count'],
            [
                ['Users',    DB::table('users')->count()],
                ['Products', DB::table('products')->count()],
                ['Articles', DB::table('articles')->count()],
                ['Contacts', DB::table('contacts')->count()],
            ]
        );
    }

    private function seedUsers(): void
    {
        $this->command->info('Seeding users...');
        $this->bulkInsert('users', function (int $i) {
            static $firstNames = ['John','Jane','Jon','Johnny','Jonathan','Johanna','James','Jack','Jake',
                'Jennifer','Jessica','Julia','Justin','Jason','Jordan','Jamie','Jean','Jeff','Jeremy',
                'Alice','Bob','Charlie','David','Emma','Frank','Grace','Henry','Iris','Kevin','Laura',
                'Michael','Nancy','Oliver','Patricia','Quinn','Robert','Sarah','Thomas','Uma','Victor',
                'Walter','Xena','Yasmine','Zoe','Aaron','Beth','Carl','Diana','Edward','Fiona'];
            static $lastNames  = ['Smith','Jones','Brown','Wilson','Taylor','Davies','Evans','Thomas',
                'Johnson','Roberts','Walker','Wright','Thompson','White','Robinson','Jackson','Harris',
                'Martin','Clark','Lewis','Lee','King','Hall','Young','Allen','Hernandez','Moore'];
            $first = $firstNames[array_rand($firstNames)];
            $last  = $lastNames[array_rand($lastNames)];
            return [
                'name'       => $first . ' ' . $last,
                'email'      => strtolower($first) . $i . '@example.com',
                'password'   => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });
    }

    private function seedProducts(): void
    {
        $this->command->info('Seeding products...');
        $this->bulkInsert('products', function (int $i) {
            static $brands    = ['Apple','Samsung','Sony','Dell','HP','Lenovo','Asus','Acer','Microsoft',
                'Google','Amazon','LG','Philips','Bosch','Nike','Adidas','Puma','Canon','Nikon','Logitech'];
            static $types     = ['Laptop','Phone','Tablet','Monitor','Keyboard','Mouse','Headphones',
                'Camera','Printer','Speaker','Watch','Charger','Cable','Case','Stand','Webcam','Router'];
            static $adjectives = ['Pro','Ultra','Max','Plus','Mini','Lite','Smart','Premium','Elite','Air'];
            $brand     = $brands[array_rand($brands)];
            $type      = $types[array_rand($types)];
            $adj       = $adjectives[array_rand($adjectives)];
            $price     = round(rand(999, 199999) / 100, 2);
            $sku       = strtoupper(substr($brand, 0, 3)) . '-' . $type[0] . '-' . str_pad($i, 6, '0', STR_PAD_LEFT);
            return [
                'name'        => "{$brand} {$type} {$adj}",
                'description' => "The {$brand} {$type} {$adj} is a high-quality product with excellent performance.",
                'sku'         => $sku,
                'brand'       => $brand,
                'price'       => $price,
                'category'    => $type,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        });
    }

    private function seedArticles(): void
    {
        $this->command->info('Seeding articles...');
        $this->bulkInsert('articles', function (int $i) {
            static $topics  = ['Laravel','PHP','JavaScript','React','Vue','Docker','Kubernetes',
                'MySQL','PostgreSQL','Redis','AWS','Linux','Git','API','REST','GraphQL',
                'Testing','Security','Performance','Deployment','Caching','Queues','Events'];
            static $verbs   = ['Introduction to','Getting Started with','Deep Dive into',
                'Advanced','Mastering','Understanding','Building with','Working with',
                'Tips for','Best Practices for'];
            static $authors = ['John Smith','Jane Doe','Alice Johnson','Bob Williams','Charlie Brown',
                'Diana Prince','Edward Norton','Fiona Green','George White','Hannah Lee'];
            static $statuses = ['draft','published','published','published','archived'];
            $topic  = $topics[array_rand($topics)];
            $verb   = $verbs[array_rand($verbs)];
            $author = $authors[array_rand($authors)];
            $status = $statuses[array_rand($statuses)];
            return [
                'title'      => "{$verb} {$topic}",
                'body'       => "This article covers {$verb} {$topic}. " . str_repeat("Learn about {$topic} fundamentals and advanced concepts. ", 5),
                'excerpt'    => "A comprehensive guide to {$verb} {$topic}.",
                'author'     => $author,
                'status'     => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });
    }

    private function seedContacts(): void
    {
        $this->command->info('Seeding contacts...');
        $this->bulkInsert('contacts', function (int $i) {
            static $firstNames = ['John','Jane','Jon','Johnny','Jonathan','Johanna','James','Jack',
                'Jennifer','Jessica','Julia','Justin','Jason','Jordan','Jean','Jamie','Jeremy',
                'Alice','Bob','Charlie','David','Emma','Frank','Grace','Henry','Kevin','Laura',
                'Michael','Nancy','Oliver','Patricia','Robert','Sarah','Thomas','Zoe','Aaron'];
            static $lastNames  = ['Smith','Jones','Brown','Wilson','Taylor','Davies','Evans',
                'Johnson','Walker','Wright','Thompson','White','Robinson','Jackson','Harris','Martin'];
            $first = $firstNames[array_rand($firstNames)];
            $last  = $lastNames[array_rand($lastNames)];
            return [
                'first_name' => $first,
                'last_name'  => $last,
                'email'      => strtolower($first) . '.' . strtolower($last) . $i . '@contact.com',
                'phone'      => '555-' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });
    }

    private function bulkInsert(string $table, callable $rowGenerator): void
    {
        $bar   = $this->command->getOutput()->createProgressBar(self::ROWS_PER_MODEL);
        $chunk = [];

        for ($i = 1; $i <= self::ROWS_PER_MODEL; $i++) {
            $chunk[] = $rowGenerator($i);

            if (count($chunk) === self::CHUNK_SIZE) {
                DB::table($table)->insert($chunk);
                $chunk = [];
                $bar->advance(self::CHUNK_SIZE);
            }
        }

        if (!empty($chunk)) {
            DB::table($table)->insert($chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->command->newLine();
    }
}
