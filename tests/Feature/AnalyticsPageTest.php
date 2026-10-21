<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The analytics page calls four SearchAnalytics methods; this keeps a package signature change from reaching users unseen. */
class AnalyticsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_with_the_empty_state_when_nothing_was_searched(): void
    {
        $this->get('/search/analytics')->assertOk()
            ->assertSee('Search analytics')
            ->assertSee('Popular searches')
            ->assertSee('Zero-result searches')
            ->assertSee('Latency by path')
            ->assertSee('Searches per day')
            ->assertSee('No searches recorded yet.')
            ->assertSee('No zero-result searches in this window.')
            ->assertSee('No latency samples yet.');
    }

    public function test_it_lists_the_logged_searches(): void
    {
        config(['fuzzy-search.analytics.enabled' => true]);
        $this->seed(DatabaseSeeder::class);

        foreach (range(1, 2) as $_) {
            $this->get('/search/users?q=john')->assertOk();
            $this->get('/search/users?q=zzqxkv')->assertOk();
        }

        $this->get('/search/analytics')->assertOk()
            ->assertSee('john')
            ->assertDontSee('No searches recorded yet.');
    }

    public function test_zero_result_searches_are_a_count_and_never_a_term(): void
    {
        config(['fuzzy-search.analytics.enabled' => true]);

        foreach (range(1, 3) as $_) {
            $this->get('/search/users?q=zzqxkv')->assertOk();
        }

        $response = $this->get('/search/analytics')->assertOk()
            ->assertSee('searches in the last 30 days found nothing')
            ->assertDontSee('zzqxkv')
            ->assertDontSee('No zero-result searches in this window.');
        $this->assertSame(3, $response->viewData('zeroSearches'));
    }

    public function test_a_term_searched_twice_that_found_nothing_is_not_in_popular_searches(): void
    {
        config(['fuzzy-search.analytics.enabled' => true]);
        $this->seed(DatabaseSeeder::class);

        foreach (range(1, 2) as $_) {
            $this->get('/search/users?q=john')->assertOk();
            $this->get('/search/users?q=zzqxkv')->assertOk();
        }

        $response = $this->get('/search/analytics')->assertOk();
        $this->assertSame(['john'], array_column($response->viewData('popular'), 'term'));
        $this->assertSame(2, $response->viewData('zeroSearches'));
    }

    public function test_a_term_searched_once_is_not_listed_on_the_public_page(): void
    {
        config(['fuzzy-search.analytics.enabled' => true]);
        $this->seed(DatabaseSeeder::class);

        $this->get('/search/users?q=pasted.private@example.com')->assertOk();
        $this->get('/search/users?q=john')->assertOk();
        $this->get('/search/users?q=john')->assertOk();

        $this->get('/search/analytics')->assertOk()
            ->assertSee('john')
            ->assertDontSee('pasted.private@example.com');
    }

    public function test_the_as_you_type_and_json_endpoints_are_not_logged(): void
    {
        config(['fuzzy-search.analytics.enabled' => true]);
        $this->seed(DatabaseSeeder::class);

        foreach (['/api/as-you-type?q=macb', '/api/search/users?q=jo', '/api/suggest?model=users&q=jo'] as $uri) {
            $this->getJson($uri)->assertOk();
            $this->getJson($uri)->assertOk();
        }
        $this->assertSame(0, DB::table('fuzzy_search_logs')->count());
        $this->assertTrue(config('fuzzy-search.analytics.enabled'), 'logging is back on for the next page');

        $this->get('/search/users?q=john')->assertOk();
        $this->assertGreaterThan(0, DB::table('fuzzy_search_logs')->count());
    }

    public function test_the_prune_command_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($event) => str_contains($event->command, 'fuzzy-search:analytics:prune'));

        $this->assertCount(1, $events);
        $this->assertSame('0 0 * * *', $events->first()->expression);
    }
}
