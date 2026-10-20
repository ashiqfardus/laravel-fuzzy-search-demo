<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LargeDatasetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackendEdgesTest extends TestCase
{
    use RefreshDatabase;

    /** WF-4: a page past the end is a page problem, not an index miss. */
    public function test_federated_page_past_the_end_links_back_to_page_one(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/search/federated?q=john&page=999')->assertOk()
            ->assertSee('No results on page 999')
            ->assertSee('Back to page 1')
            ->assertDontSee('The index found no word close enough');
    }

    /** WF-13: the 2-200 bound counts characters; 80 CJK characters are 240 bytes. */
    public function test_the_api_length_guards_count_characters(): void
    {
        $this->seed(DatabaseSeeder::class);
        $cjk = str_repeat('猫', 80);

        $this->getJson('/api/search/users?q=' . urlencode($cjk))->assertOk()->assertJsonPath('meta.query', $cjk);
        $this->getJson('/api/suggest?model=users&q=' . urlencode($cjk))->assertOk()->assertJsonPath('source', fn ($v) => $v !== null);
        $this->getJson('/api/as-you-type?q=' . urlencode($cjk))->assertOk()->assertJsonPath('meta.query', $cjk);

        $this->getJson('/api/search/users?q=' . urlencode(str_repeat('猫', 201)))->assertOk()->assertJsonPath('meta', null);
    }

    /** WF-12: a second run, and a run on a table that is already full, add nothing and do not throw. */
    public function test_the_large_seeder_fills_up_to_n_and_can_run_again(): void
    {
        config(['fuzzy-search.indexing.enabled' => false]);
        $seeder = new LargeDatasetSeeder();
        $seeder->rowsPerModel = 30;

        $seeder->run();
        $seeder->run();

        foreach (['users', 'products', 'articles', 'contacts'] as $table) {
            $this->assertSame(30, DB::table($table)->count(), $table);
        }

        $seeder->rowsPerModel = 40;
        $seeder->run();
        $this->assertSame(40, DB::table('users')->count());
    }

    /** The plain list pages say "no results on this page" too, instead of "Page 2 of 1 · 0 results". */
    public function test_every_paged_list_links_back_from_a_page_past_the_end(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['users?q=john', 'products?q=macbook', 'articles?q=search', 'relations?q=smith', 'contacts?q=john&algorithm=fuzzy', 'federated?q=john'] as $url) {
            $this->get("/search/{$url}&page=999")->assertOk()
                ->assertSee('No results on page 999')
                ->assertSee('Back to page 1')
                ->assertDontSee('0 results · ');
        }
    }

    /** The federated score badge has text for screen readers, not a title alone (FIXES-3 #36). */
    public function test_the_federated_score_badge_has_screen_reader_text(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/search/federated?q=john')->assertOk()->assertSee('<span class="sr-only">Normalised score </span>', false);
    }
}
