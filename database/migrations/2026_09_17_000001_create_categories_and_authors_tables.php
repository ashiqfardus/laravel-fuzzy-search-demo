<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('products', fn (Blueprint $table) => $table->foreignId('category_id')->nullable()->after('brand')->constrained()->nullOnDelete());
        Schema::table('articles', fn (Blueprint $table) => $table->foreignId('author_id')->nullable()->after('excerpt')->constrained()->nullOnDelete());

        // Backfill from the old string columns, then drop them.
        foreach (DB::table('products')->whereNotNull('category')->distinct()->pluck('category') as $name) {
            $id = DB::table('categories')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('products')->where('category', $name)->update(['category_id' => $id]);
        }
        foreach (DB::table('articles')->whereNotNull('author')->distinct()->pluck('author') as $name) {
            $id = DB::table('authors')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('articles')->where('author', $name)->update(['author_id' => $id]);
        }

        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('category'));
        Schema::table('articles', fn (Blueprint $table) => $table->dropColumn('author'));
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
