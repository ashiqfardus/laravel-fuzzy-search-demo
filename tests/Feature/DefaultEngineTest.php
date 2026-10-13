<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The search pages default to the BM25 index (ER-190, ER-192). A fresh clone, whose index is
 * not built, must still find its rows on the default engine; an indexed model is read from the
 * index, and an index miss offers the LIKE scan instead of running it.
 */
class DefaultEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Nothing is indexed on save: each test decides whether the index is built.
        config(['fuzzy-search.indexing.enabled' => false]);

        $author = Author::factory()->create(['name' => 'Quentin Zebulon']);
        Article::factory()->for($author)->create([
            'title'   => 'Kubernetes Operators Explained',
            'excerpt' => 'Reconcile loops for stateful workloads.',
            'status'  => 'published',
        ]);
        Product::factory()->create([
            'name'        => 'Zebrafish Aquarium Heater',
            'brand'       => 'Aquaria',
            'category_id' => Category::create(['name' => 'Aquatics'])->id,
        ]);
    }

    public function test_a_fresh_install_without_an_index_finds_rows_on_the_default_engine(): void
    {
        $this->assertSame(0, DB::table('fuzzy_index_meta')->count());

        $this->get('/search/articles?q=kubernetes')
            ->assertOk()
            ->assertSee('Operators Explained')
            ->assertSee('Index not built');

        $this->get('/search/relations?q=zebulon')
            ->assertOk()
            ->assertSee('Operators Explained')
            ->assertSee('Index not built');

        $this->get('/search/products?q=zebrafish')
            ->assertOk()
            ->assertSee('Aquarium Heater')
            ->assertSee('Index not built');
    }

    public function test_an_indexed_model_is_read_from_the_index_and_a_miss_never_scans_the_table(): void
    {
        $this->artisan('fuzzy-search:rebuild', ['model' => Article::class])->assertSuccessful();

        $this->get('/search/articles?q=kubernetes')
            ->assertOk()
            ->assertSee('Operators Explained')
            ->assertSee('Answered by the')
            ->assertDontSee('Index not built');

        $scans = $this->modelTableScans('articles', fn () => $this->get('/search/articles?q=qwxz')
            ->assertOk()
            ->assertSee('found no word close enough')
            ->assertSee('engine=like', false));
        $this->assertSame([], $scans);
        $this->assertNotSame([], $this->modelTableScans('articles', fn () => $this->get('/search/articles?q=qwxz&engine=like')));

        $this->get('/search/articles?q=kubernetes&engine=like')
            ->assertOk()
            ->assertSee('Operators Explained')
            ->assertDontSee('Answered by the');

        $this->get('/search/relations?q=zebulon')
            ->assertOk()
            ->assertSee('Zebulon</mark>', false)
            ->assertSee('-&gt;useInvertedIndex()', false)
            ->assertDontSee('Index not built');

        $scans = $this->modelTableScans('articles', fn () => $this->get('/search/relations?q=qwxz')
            ->assertOk()
            ->assertSee('found no word close enough'));
        $this->assertSame([], $scans);
        $this->assertNotSame([], $this->modelTableScans('articles', fn () => $this->get('/search/relations?q=qwxz&engine=like')));

        $this->artisan('fuzzy-search:rebuild', ['model' => Product::class])->assertSuccessful();

        $this->get('/search/products?q=zebrafish')
            ->assertOk()
            ->assertSee('Aquarium Heater')
            ->assertSee('Answered by the')
            ->assertDontSee('Index not built');

        $scans = $this->modelTableScans('products', fn () => $this->get('/search/products?q=qwxz')
            ->assertOk()
            ->assertSee('found no word close enough')
            ->assertSee('engine=like', false));
        $this->assertSame([], $scans);
        $this->assertNotSame([], $this->modelTableScans('products', fn () => $this->get('/search/products?q=qwxz&engine=like')));
    }

    public function test_federated_search_answers_through_like_until_every_model_is_indexed_then_reads_only_the_index(): void
    {
        User::factory()->create(['name' => 'Kubernetes Kate']);
        Contact::factory()->create(['first_name' => 'Kubernetes', 'last_name' => 'Kowalski']);

        $this->get('/search/federated?q=kubernetes')
            ->assertOk()
            ->assertSee('Operators Explained')
            ->assertSee('Index not built')
            ->assertSee('fuzzy-search:rebuild "App\\Models\\User"', false);

        foreach ([User::class, Product::class, Article::class, Contact::class] as $model) {
            $this->artisan('fuzzy-search:rebuild', ['model' => $model])->assertSuccessful();
        }

        foreach (['/search/federated?q=kubernetes', '/search/federated?q=zebrafish', '/search/federated?q=qwxz'] as $uri) {
            $scans = [];
            foreach (['users', 'products', 'articles', 'contacts'] as $table) {
                $scans = array_merge($scans, $this->modelTableScans($table, fn () => $this->get($uri)->assertOk()->assertDontSee('Index not built')));
            }
            $this->assertSame([], $scans, $uri);
        }

        $this->get('/search/federated?q=kubernetes')
            ->assertSee('Operators Explained')
            ->assertSee('Kubernetes Kate')
            ->assertSee('Kowalski');
        $this->get('/search/federated?q=zebrafish')->assertSee('Aquarium Heater');
        $this->get('/search/federated?q=qwxz')->assertSee('found no word close enough in any model');
    }

    public function test_an_unknown_preset_falls_back_to_ecommerce_instead_of_failing(): void
    {
        $this->get('/search/products?q=zebrafish&preset=bogus')
            ->assertOk()
            ->assertSee('Aquarium Heater');
    }

    /** The LIKE reads of $table that $request runs. */
    private function modelTableScans(string $table, \Closure $request): array
    {
        $scans = [];
        DB::listen(function ($query) use ($table, &$scans) {
            if (preg_match('/from "' . $table . '"/i', $query->sql) && stripos($query->sql, ' like ') !== false) {
                $scans[] = $query->sql;
            }
        });

        $request();

        return $scans;
    }
}
