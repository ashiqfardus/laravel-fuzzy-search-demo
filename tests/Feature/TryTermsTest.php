<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the README and the pages tell a visitor to try finds what they say, on the default seed.
 */
class TryTermsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_every_readme_playground_example_parses_and_finds_rows(): void
    {
        $readme = file_get_contents(base_path('README.md'));
        $section = preg_match('/### 4\. Extended syntax playground(.*?)\n### /s', $readme, $m) ? $m[1] : '';
        preg_match_all('/^\| `(.+?)` \|/m', $section, $rows);
        $examples = array_map(fn (string $q) => str_replace('\|', '|', $q), $rows[1]);
        $this->assertNotEmpty($examples);

        $failed = [];
        foreach ($examples as $query) {
            $page = $this->get('/search/playground?q=' . urlencode($query))->assertOk();

            if ($page->viewData('error') !== null || $page->viewData('results')->total() === 0) {
                $failed[$query] = $page->viewData('error') ?? 'no rows';
            }
        }
        $this->assertSame([], $failed);
    }
}
