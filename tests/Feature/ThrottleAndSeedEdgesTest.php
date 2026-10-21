<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Database\Factories\ProductFactory;
use Database\Seeders\FixedContactsSeeder;
use Database\Seeders\HugeDatasetSeeder;
use Database\Seeders\LargeDatasetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ThrottleAndSeedEdgesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_is_throttled_like_the_other_pages(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->get('/')->assertOk();
        }

        $this->get('/')->assertStatus(429);
    }

    public function test_the_home_page_counts_are_cached_briefly(): void
    {
        User::factory()->count(2)->create();
        $this->get('/')->assertOk();
        $this->assertSame(2, Cache::get('demo.counts')['users']);

        User::factory()->create();
        $this->get('/')->assertOk();
        $this->assertSame(2, Cache::get('demo.counts')['users'], 'a count is served for the TTL, not re-read per request');
    }

    public function test_the_like_scan_has_its_own_smaller_budget(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->get('/search/users?q=john&engine=like')->assertOk();
        }

        $this->get('/search/users?q=john&engine=like')->assertStatus(429);
        // The index engine is still within the page budget.
        $this->get('/search/users?q=john')->assertOk();
    }

    /** After a gap in the ids the user count is below the highest id; the e-mail numbers must not repeat. */
    public function test_the_huge_seeder_after_the_large_one_does_not_reuse_email_numbers(): void
    {
        config(['fuzzy-search.indexing.enabled' => false]);
        $large = new LargeDatasetSeeder();
        $large->rowsPerModel = 60;
        $large->run();
        DB::table('users')->where('id', '<=', 20)->delete();

        $huge = new HugeDatasetSeeder();
        $huge->targetUsers = 400;
        $huge->run();

        $this->assertSame(400, DB::table('users')->count());
    }

    public function test_the_large_seeder_uses_the_product_factorys_category_names(): void
    {
        config(['fuzzy-search.indexing.enabled' => false]);
        $seeder = new LargeDatasetSeeder();
        $seeder->rowsPerModel = 60;
        $seeder->run();

        $known = array_unique(array_column(ProductFactory::catalog(), 'category'));
        $used = DB::table('categories')->pluck('name')->all();
        $this->assertNotEmpty($used);
        $this->assertSame([], array_diff($used, $known));
    }

    public function test_the_fixed_contacts_are_keyed_on_their_email_not_their_first_name(): void
    {
        Contact::factory()->create(['first_name' => 'John']);

        (new FixedContactsSeeder())->run();
        (new FixedContactsSeeder())->run();

        $this->assertSame(2, Contact::where('first_name', 'John')->count(), 'the hint row is added beside a random John');
        $this->assertSame(count(FixedContactsSeeder::FIRST_NAMES) + 1, Contact::count());
    }
}
