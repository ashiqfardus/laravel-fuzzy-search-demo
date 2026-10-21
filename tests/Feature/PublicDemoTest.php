<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Author;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\HugeDatasetSeeder;
use Database\Seeders\LargeDatasetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** What a visitor of the hosted demo is told, and what a visitor can make the server do. */
class PublicDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_benchmarks_page_spends_the_ten_a_minute_table_scan_budget(): void
    {
        foreach (range(1, 10) as $_) {
            $this->get('/search/benchmarks?q=john')->assertOk();
        }

        $this->get('/search/benchmarks?q=john')->assertStatus(429)->assertSee('10 of them may be LIKE table scans');
        // The page budget of 30 is a different bucket: a page without a scan still answers.
        $this->get('/search/capability-matrix')->assertOk();
    }

    public function test_the_readme_describes_the_limits_the_routes_enforce(): void
    {
        $readme = file_get_contents(base_path('README.md'));

        $this->assertStringNotContainsString('throttle:30,1', $readme);
        $this->assertStringContainsString('10/min of them LIKE scans', $readme);
        $this->assertStringContainsString('TRUSTED_PROXIES', $readme);
        $this->assertStringContainsString('**Privacy:**', $readme);
    }

    public function test_the_partial_index_notice_does_not_claim_the_index_is_empty(): void
    {
        User::factory()->create(['name' => 'Indexed Person']);
        config(['fuzzy-search.indexing.enabled' => false]);
        User::factory(2)->create(['name' => 'Unindexed Person']);

        $this->get('/search/users?q=person')->assertOk()
            ->assertSee('does not cover every row of this model')
            ->assertDontSee('holds no rows');
    }

    public function test_the_scout_page_says_scout_is_not_installed(): void
    {
        $this->get('/search/scout-demo')->assertOk()
            ->assertSee('does not install Scout')
            ->assertSee('The index search behind the Scout driver');
        $this->assertFalse(class_exists(\Laravel\Scout\Searchable::class));
    }

    public function test_the_search_form_resets_its_button_when_a_page_is_restored_from_history(): void
    {
        $this->get('/search/users')->assertOk()->assertSee('@pageshow.window="if ($event.persisted) loading = false"', false);
    }

    public function test_the_algorithm_and_fallback_are_disabled_on_the_index_engine_only(): void
    {
        $this->get('/search/users')->assertOk()
            ->assertSee(':disabled="engine === \'bm25\'"', false)
            ->assertSee("engine: 'bm25'", false);
        $this->get('/search/users?engine=like')->assertOk()->assertSee("engine: 'like'", false);
    }

    public function test_the_error_pages_use_the_site_layout_and_need_no_database(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('No such page')->assertSee('Back to the dashboard');

        DB::enableQueryLog();
        $this->assertStringContainsString('Something went wrong', view('errors.500')->render());
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_the_soundex_hint_names_users_the_default_seed_has(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/search/capability-matrix?q=zzqxkv')->assertOk()
            ->assertSee('Try <span class="code">john</span> or <span class="code">jon</span>', false);
        foreach (['john', 'jon'] as $term) {
            $page = $this->get('/search/capability-matrix?q=' . $term)->assertOk();
            $this->assertGreaterThan(0, $page->viewData('results')['soundex']['rows']->count(), $term);
        }
    }

    public function test_the_articles_and_smart_pages_load_authors_in_one_query(): void
    {
        $author = Author::create(['name' => 'Alex Writer']);
        Article::factory(6)->create(['title' => 'Searchable story', 'author_id' => $author->id]);

        foreach (['/search/articles?q=story', '/search/smart?q=story'] as $uri) {
            DB::enableQueryLog();
            $this->get($uri)->assertOk()->assertSee('Alex Writer');
            $authorQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from "authors"') || str_contains($q['query'], 'from `authors`'))->count();
            DB::flushQueryLog();
            DB::disableQueryLog();

            $this->assertLessThanOrEqual(1, $authorQueries, $uri);
        }
    }

    public function test_the_bulk_seeders_refuse_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        foreach ([new LargeDatasetSeeder(), new HugeDatasetSeeder()] as $seeder) {
            try {
                $seeder->run();
                $this->fail(get_class($seeder) . ' ran in production');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('disabled in production', $e->getMessage());
            }
        }
        $this->assertSame(0, DB::table('users')->count());
    }

    public function test_suggest_answers_for_every_model(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['products' => 'mac', 'articles' => 'a', 'contacts' => 'ste', 'users' => 'jo'] as $model => $q) {
            $this->getJson("/api/suggest?model={$model}&q=" . ($q === 'a' ? 'ar' : $q))->assertOk()
                ->assertJsonStructure(['suggestions', 'source', 'latency_ms']);
        }
        $this->getJson('/api/suggest?model=contacts&q=ste')->assertOk()->assertJsonPath('suggestions.0', fn ($v) => is_string($v) && $v !== '');
    }
}
