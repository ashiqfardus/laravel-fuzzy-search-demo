<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HugeDatasetSeeder extends Seeder
{
    private const TARGET_USERS = 1_000_000;
    private const CHUNK_SIZE   = 2_000;

    public function run(): void
    {
        $current = DB::table('users')->count();
        $needed  = max(0, self::TARGET_USERS - $current);

        if ($needed === 0) {
            $this->command->info("Already at {$current} users. Nothing to seed.");
            return;
        }

        $this->command->info("Current users: {$current}. Adding {$needed} more to reach 1M...");

        static $firstNames = [
            'John','Jane','Jon','Johnny','Jonathan','Johanna','James','Jack','Jake',
            'Jennifer','Jessica','Julia','Justin','Jason','Jordan','Jamie','Jean','Jeff',
            'Alice','Bob','Charlie','David','Emma','Frank','Grace','Henry','Iris','Kevin',
            'Laura','Michael','Nancy','Oliver','Patricia','Quinn','Robert','Sarah','Thomas',
            'Uma','Victor','Walter','Xena','Yasmine','Zoe','Aaron','Beth','Carl','Diana',
            'Edward','Fiona','George','Hannah','Ivan','Jade','Kyle','Lisa','Mark','Nina',
            'Oscar','Paula','Ryan','Sandra','Tyler','Ursula','Vince','Wendy','Xavier','Yvonne',
            'Zachary','Anna','Brian','Christine','Derek','Elena','Felix','Gloria','Hugo','Isabel',
        ];
        static $lastNames = [
            'Smith','Jones','Brown','Wilson','Taylor','Davies','Evans','Thomas','Johnson',
            'Roberts','Walker','Wright','Thompson','White','Robinson','Jackson','Harris',
            'Martin','Clark','Lewis','Lee','King','Hall','Young','Allen','Moore','Hill',
            'Scott','Green','Adams','Baker','Nelson','Carter','Mitchell','Perez','Turner',
            'Phillips','Campbell','Parker','Edwards','Collins','Stewart','Sanchez','Morris',
            'Rogers','Reed','Cook','Morgan','Bell','Murphy','Bailey','Rivera','Cooper','Cox',
            'Howard','Ward','Torres','Peterson','Gray','Ramirez','James','Watson','Brooks',
        ];

        $now   = date('Y-m-d H:i:s');
        $bar   = $this->command->getOutput()->createProgressBar($needed);
        $chunk = [];
        $count = 0;
        $i     = $current;

        while ($count < $needed) {
            $first = $firstNames[array_rand($firstNames)];
            $last  = $lastNames[array_rand($lastNames)];

            $chunk[] = [
                'name'       => $first . ' ' . $last,
                'email'      => strtolower($first) . ($i++) . '@example.com',
                'password'   => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $count++;

            if (count($chunk) === self::CHUNK_SIZE) {
                DB::table('users')->insert($chunk);
                $chunk = [];
                $bar->advance(self::CHUNK_SIZE);
            }
        }

        if (!empty($chunk)) {
            DB::table('users')->insert($chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Total users: ' . DB::table('users')->count());
    }
}
