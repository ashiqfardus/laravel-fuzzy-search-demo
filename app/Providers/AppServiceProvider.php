<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Separate keys: a page load and the as-you-type requests it makes must not spend
        // the same per-IP budget (the default throttle key is the IP alone, for both).
        // A LIKE table scan (?engine=like) also spends a smaller budget of its own.
        RateLimiter::for('demo-pages', fn (Request $request) => array_filter([
            Limit::perMinute(30)->by('pages|'.$request->ip()),
            $request->input('engine') === 'like' ? Limit::perMinute(10)->by('like|'.$request->ip()) : null,
        ]));
        RateLimiter::for('demo-api', fn (Request $request) => Limit::perMinute(60)->by('api|'.$request->ip()));
    }
}
