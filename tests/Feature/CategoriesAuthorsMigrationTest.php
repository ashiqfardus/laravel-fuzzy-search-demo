<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The categories/authors migration on the demo's schema as it stood before it (products.category
 * and articles.author as strings): it carries every name over, and running it again, or on a
 * database where part of it exists already, changes nothing and does not fail.
 */
class CategoriesAuthorsMigrationTest extends TestCase
{
    private const MIGRATION = '2026_09_17_000001_create_categories_and_authors_tables';

    private function migration(): object
    {
        return require database_path('migrations/'.self::MIGRATION.'.php');
    }

    /** Migrate a fresh database up to the migration before this one, then add legacy rows. */
    private function oldSchemaWithRows(): void
    {
        $earlier = collect(glob(database_path('migrations/*.php')))
            ->reject(fn (string $file) => str_contains($file, self::MIGRATION))
            ->filter(fn (string $file) => basename($file) < self::MIGRATION)
            ->map(fn (string $file) => 'database/migrations/'.basename($file))
            ->values()->all();

        Artisan::call('migrate:fresh', ['--path' => $earlier]);

        $now = now();
        foreach ([['A-1', 'Laptop'], ['A-2', 'Laptop'], ['A-3', 'Phone'], ['A-4', null]] as [$sku, $category]) {
            DB::table('products')->insert(['name' => $sku, 'sku' => $sku, 'category' => $category, 'price' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach ([['One', 'Jane Doe'], ['Two', 'Jane Doe'], ['Three', 'Bob Williams']] as [$title, $author]) {
            DB::table('articles')->insert(['title' => $title, 'body' => 'b', 'author' => $author, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    private function assertBackfilled(): void
    {
        $this->assertFalse(Schema::hasColumn('products', 'category'));
        $this->assertFalse(Schema::hasColumn('articles', 'author'));
        $this->assertSame(['Laptop', 'Phone'], DB::table('categories')->orderBy('name')->pluck('name')->all());
        $this->assertSame(['Bob Williams', 'Jane Doe'], DB::table('authors')->orderBy('name')->pluck('name')->all());

        $categoryOf = DB::table('products')->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->orderBy('products.sku')->pluck('categories.name', 'products.sku')->all();
        $this->assertSame(['A-1' => 'Laptop', 'A-2' => 'Laptop', 'A-3' => 'Phone', 'A-4' => null], $categoryOf);

        $authorOf = DB::table('articles')->join('authors', 'authors.id', '=', 'articles.author_id')
            ->orderBy('articles.title')->pluck('authors.name', 'articles.title')->all();
        $this->assertSame(['One' => 'Jane Doe', 'Three' => 'Bob Williams', 'Two' => 'Jane Doe'], $authorOf);
    }

    public function test_it_carries_the_old_names_over_and_a_second_run_changes_nothing(): void
    {
        $this->oldSchemaWithRows();

        Artisan::call('migrate', ['--path' => 'database/migrations/'.self::MIGRATION.'.php']);
        $this->assertBackfilled();
        $before = [DB::table('categories')->get()->all(), DB::table('authors')->get()->all(), DB::table('products')->orderBy('id')->get()->all()];

        $this->migration()->up();

        $this->assertBackfilled();
        $this->assertEquals($before, [DB::table('categories')->get()->all(), DB::table('authors')->get()->all(), DB::table('products')->orderBy('id')->get()->all()]);
    }

    public function test_it_finishes_a_run_that_stopped_partway(): void
    {
        $this->oldSchemaWithRows();
        // What a failed MySQL run leaves: the tables, a name already inserted, the new columns.
        $this->migration()->up();
        Schema::table('products', fn ($table) => $table->string('category')->nullable());
        DB::table('products')->where('sku', 'A-1')->update(['category' => 'Laptop', 'category_id' => null]);
        DB::table('products')->where('sku', 'A-2')->update(['category' => 'Laptop', 'category_id' => null]);
        DB::table('products')->where('sku', 'A-3')->update(['category' => 'Phone', 'category_id' => null]);
        DB::table('categories')->where('name', 'Phone')->delete();

        $this->migration()->up();

        $this->assertFalse(Schema::hasColumn('products', 'category'));
        $this->assertSame(['Laptop', 'Phone'], DB::table('categories')->orderBy('name')->pluck('name')->all());
        $this->assertSame(3, DB::table('products')->whereNotNull('category_id')->count());
    }

    protected function tearDown(): void
    {
        // Leave a fully migrated database behind (MySQL keeps it between tests).
        Artisan::call('migrate:fresh');
        parent::tearDown();
    }
}
