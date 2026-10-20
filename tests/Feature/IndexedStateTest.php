<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** WF-9: an index that holds fewer documents than the table has rows is not a built index. */
class IndexedStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_partial_index_shows_the_index_not_built_notice(): void
    {
        User::factory()->create(['name' => 'Indexed Person']); // indexed on save: the meta row exists
        config(['fuzzy-search.indexing.enabled' => false]);
        User::factory(2)->create(['name' => 'Unindexed Person']);

        $this->get('/search/users?q=person')->assertOk()->assertSee('Index not built')->assertSee('3 results');

        $this->artisan('fuzzy-search:rebuild', ['model' => User::class])->assertSuccessful();
        $this->get('/search/users?q=person')->assertOk()->assertDontSee('Index not built');
    }

    /** A second look in the same minute does not count the table again. */
    public function test_the_row_count_is_cached_between_requests(): void
    {
        User::factory()->create(['name' => 'Indexed Person']);
        $this->get('/search/users?q=person')->assertOk();

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->get('/search/users?q=person')->assertOk();
        $counts = array_filter(\Illuminate\Support\Facades\DB::getQueryLog(), fn ($q) => preg_match('/select count\(\*\) as aggregate from "users"/i', $q['query']));

        $this->assertSame([], array_values($counts));
    }
}
