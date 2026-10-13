<?php

namespace Database\Seeders;

use App\Models\Contact;
use Illuminate\Database\Seeder;

/**
 * The contacts the contact page's hints name: Soundex sound-alikes (steven: Stefan, Stephen;
 * jon: John, Johnny; katherine: Kathryn; eric: Erik, Erich) and the near misses it never matches
 * because they start with another letter (Filip, Catherine). The random 40 are drawn from a name
 * list, so without these rows a hint could name a contact the database does not hold.
 *
 * Re-runnable: a name that any contact already has is skipped, so running it again (on its own,
 * `php artisan db:seed --class=FixedContactsSeeder`, or through DatabaseSeeder) adds nothing twice.
 */
class FixedContactsSeeder extends Seeder
{
    public const FIRST_NAMES = [
        'Stefan', 'Stephen', 'Steven',
        'John', 'Johnny', 'Jon',
        'Kathryn', 'Katherine', 'Catherine',
        'Erik', 'Erich', 'Eric',
        'Filip',
    ];

    public function run(): void
    {
        foreach (self::FIRST_NAMES as $firstName) {
            Contact::firstOrCreate(
                ['first_name' => $firstName],
                Contact::factory()->raw(['first_name' => $firstName])
            );
        }
    }
}
