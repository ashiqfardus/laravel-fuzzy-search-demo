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
        // Saves reach the BM25 index (the package ships observer indexing off), synchronously:
        // the demo runs no queue worker, so saves and reindexRelated() take effect immediately.
        config(['fuzzy-search.indexing.enabled' => true, 'fuzzy-search.indexing.async' => false]);

        // Persist search analytics (fuzzy_search_logs) so /search/analytics has data to show.
        config(['fuzzy-search.analytics.enabled' => true, 'fuzzy-search.analytics.queue' => null]);

        // Whitespace runs for Latin/Cyrillic/Indic, character bigrams for CJK runs — every
        // existing Latin index term stays byte-identical, so /search/i18n is the only page
        // that needs a rebuild to see it.
        config(['fuzzy-search.indexing.tokenizer' => \Ashiqfardus\LaravelFuzzySearch\Indexing\ScriptAwareTokenizer::class]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
