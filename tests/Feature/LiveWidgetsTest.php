<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** The three live widgets (WG-M3, M4, M5). Their behaviour was checked in Playwright. */
class LiveWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('pages|127.0.0.1');
        RateLimiter::clear('api|127.0.0.1');
    }

    /** WG-M3: the server's error and the throttle are shown, not "no results". */
    public function test_each_widget_shows_the_servers_error_and_a_throttle_state(): void
    {
        foreach (['/search/products', '/search/smart', '/search/playground'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('x-text="error"', $html, $url);
            $this->assertStringContainsString('resp.status === 429', $html, $url);
            $this->assertStringContainsString('!resp.ok', $html, $url);
            $this->assertStringContainsString('role="status" aria-live="polite"', $html, $url); // WG-M5
        }
    }

    /** WG-M4: as-you-type keystrokes do not spend the page budget. */
    public function test_api_requests_do_not_use_up_the_page_throttle(): void
    {
        foreach (range(1, 40) as $i) {
            $this->getJson('/api/as-you-type?q=macb')->assertOk();
        }

        $this->get('/search/users')->assertOk();
    }

    /** WG-M4: page 31 in a minute gets a themed page with the nav and a way back. */
    public function test_the_429_page_is_themed(): void
    {
        foreach (range(1, 30) as $i) {
            $this->get('/search/users')->assertOk();
        }

        $this->get('/search/users')
            ->assertStatus(429)
            ->assertSee('Slow down a little')
            ->assertSee('aria-label="Primary"', false)
            ->assertSee(route('search.index'), false);
    }
}
