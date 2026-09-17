<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ashiqfardus\LaravelFuzzySearch\Traits\Searchable;

class Article extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['title', 'body', 'excerpt', 'author_id', 'status'];

    protected array $searchable = [
        'columns' => [
            'title' => 10,
            'body' => 5,
            'excerpt' => 3,
            'author.name' => 4,
        ],
        'algorithm' => 'fuzzy',
        'typo_tolerance' => 2,
        'reindex_on' => ['author_id'],
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * What gets indexed on the BM25 path — includes the related author name so
     * searching an inverted index still reaches articles by author.
     */
    public function searchableText(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'excerpt' => $this->excerpt,
            'author.name' => $this->author?->name,
        ];
    }

    /**
     * Eager load the author during index rebuilds so searchableText() never N+1s.
     */
    public function searchIndexQuery(Builder $query): Builder
    {
        return $query->with('author');
    }
}
