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

    public function test_the_try_terms_the_pages_name_find_what_they_say(): void
    {
        $this->get('/search/smart?q=macbok')->assertOk()->assertSee('MacBook', false);
        $this->get('/search/scout-demo?q=john')->assertOk()->assertSee('John Doe');

        $playground = $this->get('/search/playground?q=' . urlencode('^Jane Smith$'))->assertOk();
        $this->assertSame(1, $playground->viewData('results')->total());

        $first = $this->getJson('/api/as-you-type?q=samsu')->assertOk()->json('data.0.name');
        $this->assertStringContainsString('Samsung', (string) $first);
    }

    public function test_the_dashboard_and_readme_count_seven_algorithms(): void
    {
        $this->get('/')->assertOk()->assertSee('Seven algorithms')->assertDontSee('Eight algorithms');
        $this->assertDoesNotMatchRegularExpression('/\\beight\\b/i', file_get_contents(base_path('README.md')));
    }

    public function test_the_scout_title_escapes_the_query_once(): void
    {
        $this->get('/search/scout-demo?q=' . urlencode("O'Brien"))
            ->assertOk()
            ->assertSee('Results for “O&#039;Brien”', false)
            ->assertDontSee('&amp;#039;', false);
    }

    public function test_the_readme_links_only_to_files_that_exist(): void
    {
        preg_match_all('/\]\((?!https?:|#|mailto:)([^)#\s]+)/', file_get_contents(base_path('README.md')), $m);

        $missing = array_values(array_filter($m[1], fn (string $path) => ! file_exists(base_path($path))));

        $this->assertSame([], $missing);
    }
}
