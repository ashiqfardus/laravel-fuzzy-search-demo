<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LargeDatasetSeeder extends Seeder
{
    private const CHUNK_SIZE = 1_000;

    /** Rows each table is filled up to. A property so a test can fill a few instead of 100k. */
    public int $rowsPerModel = 100_000;

    public function run(): void
    {
        $this->say("Filling each table up to {$this->rowsPerModel} rows with bulk inserts...");

        $this->seedUsers();
        $this->seedProducts();
        $this->seedArticles();
        $this->seedContacts();

        $this->say('Done.');
        $this->command?->table(
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
        $this->say('Seeding users...');
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
        $this->say('Seeding products...');
        $categoryIds = [];
        $this->bulkInsert('products', function (int $i) use (&$categoryIds) {
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
            $categoryIds[$type] ??= DB::table('categories')->where('name', $type)->value('id')
                ?? DB::table('categories')->insertGetId(['name' => $type, 'created_at' => now(), 'updated_at' => now()]);
            return [
                'name'        => "{$brand} {$type} {$adj}",
                'description' => "The {$brand} {$type} {$adj} is a high-quality product with excellent performance.",
                'sku'         => $sku,
                'brand'       => $brand,
                'price'       => $price,
                'category_id' => $categoryIds[$type],
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        });
    }

    private function seedArticles(): void
    {
        $this->say('Seeding articles...');
        $authorIds = [];
        $this->bulkInsert('articles', function (int $i) use (&$authorIds) {
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
            $authorIds[$author] ??= DB::table('authors')->where('name', $author)->value('id')
                ?? DB::table('authors')->insertGetId(['name' => $author, 'created_at' => now(), 'updated_at' => now()]);
            return [
                'title'      => "{$verb} {$topic}",
                'body'       => "This article covers {$verb} {$topic}. " . str_repeat("Learn about {$topic} fundamentals and advanced concepts. ", 5),
                'excerpt'    => "A comprehensive guide to {$verb} {$topic}.",
                'author_id'  => $authorIds[$author],
                'status'     => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });
    }

    private function seedContacts(): void
    {
        $this->say('Seeding contacts...');
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

    private function say(string $message): void
    {
        $this->command?->info($message);
    }

    /**
     * Fills the table up to $rowsPerModel (a second run, or a run after HugeDatasetSeeder, adds
     * nothing instead of colliding on the unique emails). New rows are numbered from the highest
     * id, so their emails and SKUs cannot repeat an existing row's.
     */
    private function bulkInsert(string $table, callable $rowGenerator): void
    {
        $have = DB::table($table)->count();

        if ($have >= $this->rowsPerModel) {
            $this->say("{$table} already holds {$have} rows; nothing to add.");
            return;
        }

        $needed = $this->rowsPerModel - $have;
        $start  = (int) DB::table($table)->max('id') + 1;
        $bar    = $this->command?->getOutput()->createProgressBar($needed);
        $chunk  = [];

        for ($i = $start; $i < $start + $needed; $i++) {
            $chunk[] = $rowGenerator($i);

            if (count($chunk) === self::CHUNK_SIZE) {
                DB::table($table)->insert($chunk);
                $chunk = [];
                $bar?->advance(self::CHUNK_SIZE);
            }
        }

        if (!empty($chunk)) {
            DB::table($table)->insert($chunk);
            $bar?->advance(count($chunk));
        }

        $bar?->finish();
        $this->command?->newLine();
    }
}
