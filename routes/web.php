<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;

Route::get('/', [SearchController::class, 'index'])->name('search.index');
Route::get('/search/users', [SearchController::class, 'users'])->name('search.users');
Route::get('/search/products', [SearchController::class, 'products'])->name('search.products');
Route::get('/search/articles', [SearchController::class, 'articles'])->name('search.articles');
Route::get('/search/contacts', [SearchController::class, 'contacts'])->name('search.contacts');
Route::get('/search/federated', [SearchController::class, 'federated'])->name('search.federated');
Route::get('/search/smart', [SearchController::class, 'smart'])->name('search.smart');
Route::get('/api/suggest', [SearchController::class, 'suggest'])->name('api.suggest');
