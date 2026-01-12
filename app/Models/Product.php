<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ashiqfardus\LaravelFuzzySearch\Traits\Searchable;

class Product extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['name', 'description', 'sku', 'brand', 'price', 'category'];

    protected array $searchable = [
        'columns' => [
            'name' => 10,
            'description' => 5,
            'sku' => 8,
            'brand' => 6,
            'category' => 4,
        ],
        'algorithm' => 'fuzzy',
        'typo_tolerance' => 2,
    ];
}
