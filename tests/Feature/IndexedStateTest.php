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

    /** The cached count is stale-while-revalidate: no COUNT inside 600 s, and a new total_docs checks again. */
    public function test_the_count_is_not_repeated_within_600_seconds_and_a_total_docs_change_rechecks(): void
    {
        config(['cache.default' => 'array']);
        \Illuminate\Support\Facades\Cache::flush();
        User::factory()->create(['name' => 'Indexed Person']);
        $this->get('/search/users?q=person')->assertOk();

        $counts = fn () => count(array_filter(\Illuminate\Support\Facades\DB::getQueryLog(), fn ($q) => preg_match('/select count\(\*\) as aggregate from "users"/i', $q['query'])));

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->travel(599)->seconds();
        $this->get('/search/users?q=person')->assertOk();
        $this->assertSame(0, $counts());

        \Illuminate\Support\Facades\DB::table('fuzzy_index_meta')->where('model_type', User::class)->increment('total_docs');
        $this->get('/search/users?q=person')->assertOk();
        $this->assertSame(1, $counts());
    }
}
