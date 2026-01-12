<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ashiqfardus\LaravelFuzzySearch\Traits\Searchable;

class Contact extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'company'];

    protected array $searchable = [
        'columns' => [
            'first_name' => 10,
            'last_name' => 10,
            'email' => 8,
            'company' => 5,
        ],
        'algorithm' => 'levenshtein',
        'typo_tolerance' => 2,
    ];
}
