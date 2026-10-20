<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The live preview is a plain BM25 read, so it must not stand in for an operator query:
 * "!smith" used to preview the Smiths (N5-L5).
 */
class PlaygroundPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_preview_stops_and_says_so_when_the_query_has_operators(): void
    {
        $html = $this->get('/search/playground')->assertOk()->getContent();

        $this->assertStringContainsString('get hasOperators()', $html);
        $this->assertStringContainsString('this.hasOperators', $html);
        $this->assertStringContainsString('ignores operators', $html);
        $this->assertStringContainsString('data-preview-note', $html);
    }
}
