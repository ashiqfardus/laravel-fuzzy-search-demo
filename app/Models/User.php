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

    // The metaphone shadow columns are index plumbing, not part of a user's public record.
    // /api/search/users serialises this model to anonymous callers (it is a demo); a real
    // app authorises that route and hides email too.
    protected $hidden = [
        'password',
        'remember_token',
        'name_metaphone',
        'email_metaphone',
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
