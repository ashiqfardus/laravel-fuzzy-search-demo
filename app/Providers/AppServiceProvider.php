<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The demo runs no queue worker; index synchronously so saves and reindexRelated() take effect immediately.
        config(['fuzzy-search.indexing.async' => false]);

        // Persist search analytics (fuzzy_search_logs) so /search/analytics has data to show.
        config(['fuzzy-search.analytics.enabled' => true, 'fuzzy-search.analytics.queue' => null]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
