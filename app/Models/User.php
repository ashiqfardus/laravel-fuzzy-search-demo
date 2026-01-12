<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Ashiqfardus\LaravelFuzzySearch\Traits\Searchable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Searchable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected array $searchable = [
        'columns' => [
            'name' => 10,
            'email' => 8,
        ],
        'algorithm' => 'fuzzy',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
