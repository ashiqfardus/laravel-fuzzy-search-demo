<?php

namespace Tests\Feature;

use App\Models\Contact;
use Database\Seeders\FixedContactsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every sound-alike the contact page's hints name exists and is found (P6D-3): the random 40
 * contacts come from a name list, so FixedContactsSeeder adds the hinted names, once. The hints
 * follow the database (N5-M8): MySQL/MariaDB run SOUNDEX() natively; the others (PostgreSQL by
 * default, SQLite, SQL Server) run the package's phonetic pattern fallback, which matches
 * spellings, so the page names what that finds there.
 */
class ContactHintsTest extends TestCase
{
    use RefreshDatabase;

    /** The page's "Try a sound-alike" hints on MySQL/MariaDB: query => the first names it says it finds. */
    private const NATIVE_HINTS = [
        'steven'    => ['Stefan', 'Stephen'],
        'jon'       => ['John', 'Johnny'],
        'katherine' => ['Kathryn'],
        'eric'      => ['Erik', 'Erich'],
    ];

    /** The same on the pattern fallback: "jon" matches only names spelled with it there. */
    private const FALLBACK_HINTS = [
        'steven'    => ['Stefan', 'Stephen'],
        'john'      => ['Johnny'],
        'katherine' => ['Kathryn'],
        'eric'      => ['Erik', 'Erich'],
    ];

    /** query => a first name the page says it never finds, per database. */
    private const NATIVE_NEVER = ['philip' => 'Filip', 'katherine' => 'Catherine'];
    private const FALLBACK_NEVER = ['jon' => 'John', 'katherine' => 'Catherine'];

    private function native(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['fuzzy-search.indexing.enabled' => false]);
        Contact::factory(40)->create(); // the random 40, as DatabaseSeeder seeds them
        $this->seed(FixedContactsSeeder::class);
    }

    /**
     * The suite runs the fallback's hints (SQLite). To run the native ones, point it at a scratch
     * MySQL database it may refresh:
     * DB_CONNECTION=mysql DB_DATABASE=<scratch> php artisan test --filter=ContactHintsTest
     */
    public function test_each_hinted_search_returns_the_rows_it_names_and_never_the_ones_it_rules_out(): void
    {
        foreach ($this->native() ? self::NATIVE_HINTS : self::FALLBACK_HINTS as $query => $names) {
            $found = $this->get('/search/contacts?q=' . $query)->assertOk()->viewData('results')->pluck('first_name')->all();

            foreach ($names as $name) {
                $this->assertContains($name, $found, "“{$query}” should find {$name}");
            }
        }

        foreach ($this->native() ? self::NATIVE_NEVER : self::FALLBACK_NEVER as $query => $never) {
            $found = $this->get('/search/contacts?q=' . $query)->viewData('results')->pluck('first_name')->all();
            $this->assertNotContains($never, $found, "“{$query}” should never find {$never}");
        }
    }

    public function test_the_page_hints_name_the_seeded_rows(): void
    {
        $page = $this->get('/search/contacts')->assertOk();

        foreach ($this->native() ? self::NATIVE_HINTS : self::FALLBACK_HINTS as $query => $names) {
            $page->assertSee($query . '</span> finds ' . implode(', ', $names), false);

            foreach ($names as $name) {
                $this->assertTrue(Contact::where('first_name', $name)->exists(), $name);
            }
        }
    }

    public function test_the_page_says_which_soundex_this_database_runs(): void
    {
        $page = $this->get('/search/contacts')->assertOk();

        if ($this->native()) {
            $page->assertSee('runs SOUNDEX() natively')->assertSee('“philip” never finds Filip', false);
        } else {
            $page->assertSee('phonetic pattern fallback')->assertSee('“philip” finds Filip', false)
                ->assertDontSee('never finds Filip')->assertDontSee('which this demo runs on');
        }

        foreach ($this->native() ? self::NATIVE_NEVER : self::FALLBACK_NEVER as $query => $never) {
            $page->assertSee("“{$query}” never finds {$never}", false);
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
