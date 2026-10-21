<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The pass-8 UI/UX findings (WG-M2, M3-M6, L1-L9). Markup is checked here; behaviour in Playwright. */
class UiPolishTest extends TestCase
{
    use RefreshDatabase;

    /** WG-M2: 13 links wrap instead of hiding behind a scroll strip. */
    public function test_the_primary_nav_wraps_instead_of_scrolling(): void
    {
        $html = $this->get('/search/users')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<nav class="[^"]*flex-wrap[^"]*" aria-label="Primary"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<nav class="[^"]*overflow-x-auto[^"]*" aria-label="Primary"/', $html);
    }

    /** WG-L2 */
    public function test_the_mobile_nav_marks_the_current_page_and_closes_on_escape(): void
    {
        $html = $this->get('/search/users')->assertOk()->getContent();
        $mobile = substr($html, strpos($html, 'id="mobile-nav"'));
        $mobile = substr($mobile, 0, strpos($mobile, '</nav>'));

        $this->assertStringContainsString('aria-current="page"', $mobile);
        $this->assertStringContainsString('@keydown.escape.window="menu = false"', $mobile);
    }

    /** WG-L1 */
    public function test_an_out_of_range_typo_tolerance_shows_the_value_used(): void
    {
        $this->get('/search/users?q=jonh&typo_tolerance=99')->assertOk()
            ->assertSee('<option value="5" selected>', false);
        $this->get('/search/users?q=jonh&typo_tolerance=-3')->assertOk()
            ->assertSee('<option value="0" selected>', false);
    }

    /** WG-L3 */
    public function test_the_clear_history_button_is_a_real_target(): void
    {
        $this->get('/search/benchmarks?q=jonh')->assertOk()->assertSee('min-w-6', false)->assertSee('px-3 py-1.5', false);
    }

    /** WG-L4 */
    public function test_a_long_query_wraps_in_the_empty_state(): void
    {
        $this->get('/search/products?q=' . str_repeat('z', 120))->assertOk()->assertSee('break-words text-ink', false);
    }

    /** WG-L5 */
    public function test_the_articles_miss_hint_does_not_offer_the_mode_already_selected(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/search/articles?q=zzqq&engine=like')->assertOk()->assertDontSee('Switch to “match any”');
        $this->get('/search/articles?q=zzqq&engine=like&match_mode=all')->assertOk()->assertSee('Switch to “match any”');
    }

    /** WG-L7 */
    public function test_the_smart_panels_are_h2_and_score_badges_have_text(): void
    {
        $this->seed(DatabaseSeeder::class);

        $smart = $this->get('/search/smart?q=iphne')->assertOk()->getContent();
        $this->assertStringNotContainsString('<h3', $smart);

        $this->get('/search/users?q=john')->assertOk()->assertSee('<span class="sr-only">Relevance score </span>', false);
    }

    /** WG-L8: placeholder text uses the muted token (5.7:1 on the field), not muted at 70% (3.55:1). */
    public function test_the_placeholder_uses_the_full_muted_colour(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression('/placeholder:text-muted\s/', $css);
        $this->assertStringNotContainsString('placeholder:text-muted/', $css);
    }

    /** WG-L9 */
    public function test_the_index_page_says_in_v3_0(): void
    {
        $this->get('/')->assertOk()->assertSee('in v3.0')->assertDontSee('from the next release');
    }

    /** Item 11: Escape ends the docked tour card from anywhere on the page, not only from inside it. */
    public function test_escape_ends_the_tour_wherever_focus_is(): void
    {
        $this->get('/')->assertOk()->assertSee('@keydown.escape.window="tour.active && tour.finish()"', false);
    }
}
