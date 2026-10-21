<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Author;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Visitor text and stored text reach the page escaped: no raw tag from a query or a model name. */
class EscapingTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD = '"><img src=x onerror=alert(1)>';

    private const PAGES = [
        '/search/users', '/search/products', '/search/relations', '/search/articles', '/search/contacts',
        '/search/federated', '/search/smart', '/search/benchmarks', '/search/scout-demo',
        '/search/playground', '/search/i18n',
    ];

    public function test_a_hostile_query_is_escaped_on_every_page(): void
    {
        foreach (self::PAGES as $page) {
            $response = $this->get($page . '?q=' . urlencode(self::PAYLOAD))->assertOk();
            $response->assertDontSee('<img src=x', false);
        }
        foreach (['like', 'bm25'] as $engine) {
            $this->get('/search/users?engine=' . $engine . '&q=' . urlencode(self::PAYLOAD))->assertOk()->assertDontSee('<img src=x', false);
        }
    }

    public function test_markup_in_a_stored_name_is_escaped_in_the_highlighted_output(): void
    {
        User::factory()->create(['name' => '<b>x</b> Markus', 'email' => 'markup@example.com']);
        Product::factory()->create(['name' => '<b>x</b> Markphone']);
        $author = Author::create(['name' => '<i>y</i> Mark Author']);
        Article::factory()->create(['title' => 'Mark article', 'author_id' => $author->id]);

        foreach (['/search/users?engine=like&q=mark', '/search/users?q=mark', '/search/products?q=mark', '/search/products?engine=like&q=mark',
                  '/search/articles?q=mark', '/search/relations?q=mark', '/search/smart?q=mark', '/search/federated?q=mark'] as $uri) {
            $this->get($uri)->assertOk()->assertDontSee('<b>x</b>', false)->assertDontSee('<i>y</i>', false);
        }
        foreach (['/api/search/users?q=mark', '/api/as-you-type?q=markph'] as $uri) {
            $this->assertStringNotContainsString('<b>x</b>', $this->getJson($uri)->assertOk()->getContent(), $uri);
        }
    }
}
