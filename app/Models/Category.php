<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function booted(): void
    {
        // Renaming a category must reindex its products — Eloquent does not do this for you.
        static::saved(fn (self $row) => Product::reindexRelated('category_id', $row->id));
    }
}
