<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A query parameter sent as an array (?q[]=x), or empty, must never fail a page: every action
 * reads q and its selects as strings (P6D-6).
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
}
