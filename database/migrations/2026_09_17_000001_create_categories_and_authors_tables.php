<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Re-runnable: every step checks what already exists, so a second run, or a run after one that
     * stopped partway (MySQL commits each DDL statement on its own), skips what is done. The
     * backfill is two set-based statements per table, not one query per distinct value.
     */
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('authors')) {
            Schema::create('authors', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', fn (Blueprint $table) => $table->foreignId('category_id')->nullable()->after('brand')->constrained()->nullOnDelete());
        }
        if (! Schema::hasColumn('articles', 'author_id')) {
            Schema::table('articles', fn (Blueprint $table) => $table->foreignId('author_id')->nullable()->after('excerpt')->constrained()->nullOnDelete());
        }

        // Backfill from the old string columns, then drop them.
        $this->backfill('products', 'category', 'category_id', 'categories');
        $this->backfill('articles', 'author', 'author_id', 'authors');

        if (Schema::hasColumn('products', 'category')) {
            Schema::table('products', fn (Blueprint $table) => $table->dropColumn('category'));
        }
        if (Schema::hasColumn('articles', 'author')) {
            Schema::table('articles', fn (Blueprint $table) => $table->dropColumn('author'));
        }
    }

    /** Insert the distinct names not yet in $lookup, then point each row at its name's id. */
    private function backfill(string $table, string $old, string $foreignKey, string $lookup): void
    {
        if (! Schema::hasColumn($table, $old)) {
            return;
        }

        // CURRENT_TIMESTAMP, not bound values: PostgreSQL types a bound parameter in a DISTINCT select
        // list as text and refuses to insert it into a timestamp column.
        DB::table($lookup)->insertUsing(
            ['name', 'created_at', 'updated_at'],
            DB::table($table)->selectRaw('DISTINCT '.$old.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP')
                ->whereNotNull($old)
                ->whereNotIn($old, DB::table($lookup)->select('name'))
        );

        DB::table($table)->whereNotNull($old)->whereNull($foreignKey)->update([
            $foreignKey => DB::raw("(select id from {$lookup} where {$lookup}.name = {$table}.{$old})"),
        ]);
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->string('category')->nullable());
        Schema::table('articles', fn (Blueprint $table) => $table->string('author')->nullable());

        DB::table('products')->whereNotNull('category_id')->update([
            'category' => DB::raw('(select name from categories where categories.id = products.category_id)'),
        ]);
        DB::table('articles')->whereNotNull('author_id')->update([
            'author' => DB::raw('(select name from authors where authors.id = articles.author_id)'),
        ]);

        Schema::table('products', function (Blueprint $table) { $table->dropConstrainedForeignId('category_id'); });
        Schema::table('articles', function (Blueprint $table) { $table->dropConstrainedForeignId('author_id'); });
        Schema::dropIfExists('authors');
        Schema::dropIfExists('categories');
    }
};
