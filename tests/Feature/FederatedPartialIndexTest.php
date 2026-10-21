<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** A federated search reads the built indexes and leaves out the models that have none. */
class FederatedPartialIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['fuzzy-search.indexing.enabled' => false]);
        User::factory()->create(['name' => 'Kubernetes Kate']);
        Product::factory()->create(['name' => 'Kubernetes Poster']);
        \App\Models\Article::factory()->create(['title' => 'Kubernetes Operators', 'status' => 'published']);
        Contact::factory()->create(['first_name' => 'Kubernetes', 'last_name' => 'Kowalski']);
    }

    private function build(array $models): void
    {
        foreach ($models as $model) {
            $this->artisan('fuzzy-search:rebuild', ['model' => $model])->assertSuccessful();
        }
    }

    private function likeScans(string $uri): array
    {
        $scans = [];
        DB::listen(function ($q) use (&$scans) {
            if (preg_match('/from [`"](users|products|articles|contacts)[`"]/i', $q->sql) && stripos($q->sql, ' like ') !== false) {
                $scans[] = $q->sql;
            }
        });
        $this->get($uri)->assertOk();

        return $scans;
    }

    public function test_one_unbuilt_model_is_left_out_and_the_others_answer_from_the_index(): void
    {
        $this->build([User::class, Product::class]); // Article and Contact stay unbuilt

        $this->assertSame([], $this->likeScans('/search/federated?q=kubernetes'));

        $this->get('/search/federated?q=kubernetes')
            ->assertSee('Kubernetes Kate')
            ->assertSee('Kubernetes Poster')
            ->assertDontSee('Kowalski')->assertDontSee('Operators')
            ->assertSee('Left out until built: Article, Contact')
            ->assertSee('fuzzy-search:rebuild "App\\Models\\Article"', false)
            ->assertSee('fuzzy-search:rebuild "App\\Models\\Contact"', false)
            ->assertDontSee('fuzzy-search:rebuild "App\\Models\\User"', false)
            ->assertSee('Normalised score');
    }

    public function test_all_indexed_shows_no_callout(): void
    {
        $this->build([User::class, Product::class, \App\Models\Article::class, Contact::class]);

        $this->get('/search/federated?q=kubernetes')->assertOk()
            ->assertDontSee('Index not built')->assertDontSee('Left out until built')->assertSee('Kowalski');
    }

    public function test_none_indexed_keeps_the_like_path(): void
    {
        $this->assertNotSame([], $this->likeScans('/search/federated?q=kubernetes'));

        $this->get('/search/federated?q=kubernetes')
            ->assertSee('Kowalski')->assertSee('Index not built')
            ->assertDontSee('Left out until built');
    }
}
