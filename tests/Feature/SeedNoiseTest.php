<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Product;
use Database\Factories\ProductFactory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The seeded rows carry real text, so the showcase pages' own example terms are not drowned in
 * index typo matches against lorem ipsum (N5-L6). Product SKUs are SKU-digits, not random letters:
 * a random SKU such as JEN or AON sits within typo distance of "john" and ranked a product beside
 * John Doe.
 */
class SeedNoiseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_federated_john_finds_no_product(): void
    {
        $results = $this->get('/search/federated?q=john')->assertOk()->viewData('results');

        $this->assertNotEmpty($results->items());
        $this->assertSame([], array_values(array_filter($results->items(), fn ($m) => $m instanceof Product)));
    }

    public function test_relations_smith_finds_only_the_articles_by_a_smith(): void
    {
        $results = $this->get('/search/relations?q=smith')->assertOk()->viewData('results');

        $this->assertGreaterThan(0, $results->total());
        $this->assertLessThan(Article::count() / 2, $results->total());
    }

    public function test_every_product_holds_its_catalog_description(): void
    {
        $catalog = collect(ProductFactory::catalog())->pluck('description', 'name');

        foreach (Product::all() as $product) {
            $this->assertSame($catalog[$product->name], $product->description, $product->name);
        }
    }
}
