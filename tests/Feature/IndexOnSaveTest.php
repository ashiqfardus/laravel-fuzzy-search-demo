<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The app turns on observer indexing (AppServiceProvider), synchronously, so a save reaches the
 * BM25 index the pages read by default without a rebuild (N5-M3).
 */
class IndexOnSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_saved_after_the_rebuild_is_found_on_the_index(): void
    {
        $category = Category::create(['name' => 'Aquatics']);
        Product::factory()->create(['name' => 'Zebrafish Aquarium Heater', 'category_id' => $category->id]);
        $this->artisan('fuzzy-search:rebuild', ['model' => Product::class])->assertSuccessful();

        $filter = Product::factory()->create(['name' => 'Zorblax Pond Filter', 'category_id' => $category->id]);

        $this->get('/search/products?q=zorblax')
            ->assertOk()
            ->assertSee('Answered by the')
            ->assertSee('Pond Filter');

        $filter->update(['name' => 'Quillback Pond Filter']);

        $this->get('/search/products?q=quillback')->assertOk()->assertSee('Pond Filter');
        $this->get('/search/products?q=zorblax')->assertOk()->assertDontSee('Pond Filter');
    }
}
