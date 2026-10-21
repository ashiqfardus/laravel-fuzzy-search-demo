<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->get('/search/users?q=john')->assertOk();
        $this->get('/search/users?q=zzqxkv')->assertOk();

        $this->get('/search/analytics')->assertOk()
            ->assertSee('john')
            ->assertSee('zzqxkv')
            ->assertDontSee('No searches recorded yet.');
    }
}
