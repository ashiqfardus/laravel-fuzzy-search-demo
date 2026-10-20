<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BenchmarksPageTest extends TestCase
{
    use RefreshDatabase;

    /** The history block listens on window; a non-bubbling event on document never reached it (N5-L4). */
    public function test_the_saved_run_event_reaches_the_history_listener(): void
    {
        $html = $this->get('/search/benchmarks?q=jonh')->assertOk()->getContent();

        $this->assertStringContainsString('@bench-run-saved.window', $html);
        $this->assertStringContainsString("window.dispatchEvent(new CustomEvent('bench-run-saved'))", $html);
        $this->assertStringNotContainsString("document.dispatchEvent(new CustomEvent('bench-run-saved'))", $html);
    }

    /** No unmeasured speed-up is quoted on the page that exists to measure it (N5-L12). */
    public function test_the_page_quotes_no_unmeasured_speedup(): void
    {
        $this->get('/search/benchmarks?q=jonh')->assertOk()->assertDontSee('5–20×')->assertSee('measured for this term');
    }
}
