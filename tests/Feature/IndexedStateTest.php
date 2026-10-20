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
}
