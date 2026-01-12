<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ashiqfardus\LaravelFuzzySearch\Traits\Searchable;

class Article extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['title', 'body', 'excerpt', 'author', 'status'];

    protected array $searchable = [
        'columns' => [
            'title' => 10,
            'body' => 5,
            'excerpt' => 3,
            'author' => 4,
        ],
        'algorithm' => 'fuzzy',
        'typo_tolerance' => 2,
    ];
}
