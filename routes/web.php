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
Route::get('/search/capability-matrix', [SearchController::class, 'capabilityMatrix'])->name('search.capability-matrix');
Route::get('/api/suggest', [SearchController::class, 'suggest'])->name('api.suggest');
Route::get('/api/search/users', [SearchController::class, 'searchUsers'])->name('api.search.users');
Route::get('/search/benchmarks', [SearchController::class, 'benchmarks'])->name('search.benchmarks');
Route::get('/search/scout-demo', [SearchController::class, 'scoutDemo'])->name('search.scout-demo');
Route::get('/search/playground', [SearchController::class, 'playground'])->name('search.playground');
