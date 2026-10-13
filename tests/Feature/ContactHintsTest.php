<?php

namespace Tests\Feature;

use App\Models\Contact;
use Database\Seeders\FixedContactsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every sound-alike the contact page's hints name exists and is found (P6D-3): the random 40
 * contacts come from a name list, so FixedContactsSeeder adds the hinted names, once.
 */
class ContactHintsTest extends TestCase
{
    use RefreshDatabase;

    /** The page's "Try a sound-alike" hints: query => the first names it says it finds. */
    private const HINTS = [
        'steven'    => ['Stefan', 'Stephen'],
        'jon'       => ['John', 'Johnny'],
        'katherine' => ['Kathryn'],
        'eric'      => ['Erik', 'Erich'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config(['fuzzy-search.indexing.enabled' => false]);
        Contact::factory(40)->create(); // the random 40, as DatabaseSeeder seeds them
        $this->seed(FixedContactsSeeder::class);
    }

    /**
     * The hints are MySQL/MariaDB SOUNDEX() results (the demo runs on MySQL). Other databases use
     * the package's phonetic pattern fallback, which matches differently (on SQLite and PostgreSQL
     * "jon" finds Jonathan and "philip" finds Filip). To run this on MySQL, point the suite at a
     * scratch database it may refresh:
     * DB_CONNECTION=mysql DB_DATABASE=<scratch> php artisan test --filter=ContactHintsTest
     */
    public function test_each_hinted_search_returns_the_rows_it_names_and_never_a_name_with_another_first_letter(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped("The examples are MySQL/MariaDB SOUNDEX; other databases' phonetic matching differs.");
        }

        foreach (self::HINTS as $query => $names) {
            $found = $this->get('/search/contacts?q=' . $query)->assertOk()->viewData('results')->pluck('first_name')->all();

            foreach ($names as $name) {
                $this->assertContains($name, $found, "“{$query}” should find {$name}");
            }
        }

        foreach (['philip' => 'Filip', 'katherine' => 'Catherine'] as $query => $never) {
            $found = $this->get('/search/contacts?q=' . $query)->viewData('results')->pluck('first_name')->all();
            $this->assertNotContains($never, $found, "“{$query}” should never find {$never}");
        }
    }

    public function test_the_page_hints_name_the_seeded_rows(): void
    {
        $page = $this->get('/search/contacts')->assertOk();

        foreach (self::HINTS as $query => $names) {
            $page->assertSee($query . '</span> finds ' . implode(', ', $names), false);

            foreach ($names as $name) {
                $this->assertTrue(Contact::where('first_name', $name)->exists(), $name);
            }
        }
    }

    public function test_the_seeder_adds_each_hinted_name_once_however_often_it_runs(): void
    {
        $count = Contact::count();
        $this->seed(FixedContactsSeeder::class);

        $this->assertSame($count, Contact::count());
        foreach (FixedContactsSeeder::FIRST_NAMES as $firstName) {
            $this->assertTrue(Contact::where('first_name', $firstName)->exists(), $firstName);
        }
    }
}
