<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ashiqfardus\LaravelFuzzySearch\Traits\Searchable;

class Product extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['name', 'description', 'sku', 'brand', 'price', 'category_id'];

    protected array $searchable = [
        'columns' => [
            'name' => 10,
            'description' => 5,
            'sku' => 8,
            'brand' => 6,
            'category.name' => 4,
        ],
        'algorithm' => 'fuzzy',
        'typo_tolerance' => 2,
        'reindex_on' => ['category_id'],
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * What gets indexed on the BM25 path — includes the related category name so
     * searching an inverted index still reaches products by category.
     */
    public function searchableText(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'brand' => $this->brand,
            'category.name' => $this->category?->name,
        ];
    }

    /**
     * Eager load the category during index rebuilds so searchableText() never N+1s.
     */
    public function searchIndexQuery(Builder $query): Builder
    {
        return $query->with('category');
    }
}
