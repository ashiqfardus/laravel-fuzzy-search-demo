<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A query parameter sent as an array (?q[]=x), or empty, must never fail a page: every action
 * reads q and its selects as strings (P6D-6). Nor may a select's value it does not offer
 * (N5-M5): contacts has no metaphone shadow columns, so ?algorithm=metaphone threw.
 */
class ArrayParameterTest extends TestCase
{
    use RefreshDatabase;

    public static function uris(): array
    {
        $uris = [
            '/?q[]=x',
            '/search/users?q[]=x',
            '/search/users?q=jon&algorithm[]=x&engine=like',
            '/search/users?q=jon&algorithm=&engine=like',
            '/search/users?q=jon&fallback[]=x&engine=like',
            '/search/users?q=jon&engine[]=x',
            '/search/products?q[]=x',
            '/search/products?q=lapto&preset[]=x',
            '/search/products?q=lapto&category[]=x',
            '/search/relations?q[]=x',
            '/search/articles?q[]=x',
            '/search/articles?q=lara&match_mode[]=all',
            '/search/contacts?q[]=x',
            '/search/contacts?q=jon&algorithm[]=x',
            '/search/contacts?q=jon&algorithm=',
            '/search/contacts?q=jon&algorithm=metaphone',
            '/search/contacts?q=jon&algorithm=trigram',
            '/search/contacts?q=jon&algorithm=bogus',
            '/search/users?q=jon&algorithm=trigram&engine=like',
            '/search/users?q=jon&algorithm=bogus&engine=like',
            '/search/federated?q[]=x',
            '/search/smart?q[]=x',
            '/search/smart?q=lara&engine[]=like',
            '/search/capability-matrix?q[]=x',
            '/search/benchmarks?q[]=x',
            '/search/benchmarks?q=john&typo[]=2',
            '/search/scout-demo?q[]=x',
            '/search/playground?q[]=x',
            '/search/i18n?q[]=x',
            '/search/i18n?q=',
            '/api/suggest?q[]=x',
            '/api/suggest?q=lar&model[]=x',
            '/api/search/users?q[]=x',
            '/api/as-you-type?q[]=x',
        ];

        return array_combine($uris, array_map(fn (string $uri) => [$uri], $uris));
    }

    #[DataProvider('uris')]
    public function test_an_array_or_empty_parameter_never_fails_the_page(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    public function test_an_algorithm_its_select_does_not_offer_falls_back_to_the_default(): void
    {
        $this->get('/search/contacts?q=jon&algorithm=metaphone')->assertOk()->assertViewHas('algorithm', 'soundex');
        $this->get('/search/users?q=jon&algorithm=trigram&engine=like')->assertOk()->assertViewHas('algorithm', 'fuzzy');
    }
}
