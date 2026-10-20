<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedAndEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    /** "Verified users only" must change a list on the default seed (N5-L7). */
    public function test_verified_only_changes_the_users_list(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class); // a reseed keeps them unverified

        $all = $this->get('/search/users?q=jon&algorithm=simple')->assertOk()->viewData('results')->count();
        $verified = $this->get('/search/users?q=jon&algorithm=simple&verified_only=1')->assertOk()->viewData('results')->count();

        $this->assertGreaterThan($verified, $all);
    }

    /** The contacts empty state follows the selected algorithm (N5-L8). */
    public function test_the_contacts_empty_state_names_the_selected_algorithm(): void
    {
        $this->get('/search/contacts?q=qqqqzz&algorithm=levenshtein')
            ->assertOk()
            ->assertSee('with Levenshtein')
            ->assertDontSee('Soundex needs a true phonetic match');

        $this->get('/search/contacts?q=qqqqzz&algorithm=soundex')
            ->assertOk()
            ->assertSee('Soundex needs a true phonetic match');
    }
}
