<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;

Route::middleware('throttle:demo-pages')->group(function () {
    Route::get('/', [SearchController::class, 'index'])->name('search.index');
    Route::get('/search/users', [SearchController::class, 'users'])->name('search.users');
    Route::get('/search/products', [SearchController::class, 'products'])->name('search.products');
    Route::get('/search/relations', [SearchController::class, 'relations'])->name('search.relations');
    Route::get('/search/articles', [SearchController::class, 'articles'])->name('search.articles');
    Route::get('/search/contacts', [SearchController::class, 'contacts'])->name('search.contacts');
    Route::get('/search/federated', [SearchController::class, 'federated'])->name('search.federated');
    Route::get('/search/smart', [SearchController::class, 'smart'])->name('search.smart');
    Route::get('/search/capability-matrix', [SearchController::class, 'capabilityMatrix'])->name('search.capability-matrix');
    Route::get('/search/benchmarks', [SearchController::class, 'benchmarks'])->name('search.benchmarks');
    Route::get('/search/scout-demo', [SearchController::class, 'scoutDemo'])->name('search.scout-demo');
    Route::get('/search/playground', [SearchController::class, 'playground'])->name('search.playground');
    Route::get('/search/analytics', [SearchController::class, 'analytics'])->name('search.analytics');
    Route::get('/search/i18n', [SearchController::class, 'i18n'])->name('search.i18n');
});

Route::middleware('throttle:demo-api')->group(function () {
    Route::get('/api/suggest', [SearchController::class, 'suggest'])->name('api.suggest');
    Route::get('/api/search/users', [SearchController::class, 'searchUsers'])->name('api.search.users');
    Route::get('/api/as-you-type', [SearchController::class, 'asYouType'])->name('api.as-you-type');
});
