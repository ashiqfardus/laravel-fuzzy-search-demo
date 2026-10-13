<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use App\Models\Product;
use App\Models\Article;
use App\Models\Contact;
use App\Models\Category;
use Ashiqfardus\LaravelFuzzySearch\Events\FuzzySearchExecuted;
use Ashiqfardus\LaravelFuzzySearch\Facades\SearchAnalytics;
use Ashiqfardus\LaravelFuzzySearch\FederatedSearch;
use Ashiqfardus\LaravelFuzzySearch\Http\Resources\FuzzySearchCollection;

class SearchController extends Controller
{
    /**
     * Main search dashboard
     */
    public function index()
    {
        return view('search.index', [
            'stats' => [
                'users' => User::count(),
                'products' => Product::count(),
                'articles' => Article::count(),
                'contacts' => Contact::count(),
            ]
        ]);
    }

    /**
     * Search users with various options
     */
    public function users(Request $request)
    {
        $query = $this->term($request);
        $algorithm = $request->input('algorithm', 'fuzzy');
        $typoTolerance = (int) $request->input('typo_tolerance', 2);
        $fallback = $request->input('fallback', '');
        if (!in_array($fallback, ['fuzzy', 'levenshtein', 'soundex', 'metaphone', 'simple'], true) || $fallback === $algorithm) {
            $fallback = '';
        }
        $verifiedOnly = $request->boolean('verified_only');

        $results = collect();
        $debugInfo = [];
        $attempts = $this->recordSearchAttempts();

        if ($query) {
            try {
                $searchQuery = User::search($query)
                    ->using($algorithm)
                    ->typoTolerance($typoTolerance)
                    ->withRelevance()
                    ->highlight('mark');

                if ($fallback !== '') {
                    // Re-runs the search with this algorithm only if the primary finds nothing.
                    $searchQuery->fallback($fallback);
                }

                if ($verifiedOnly) {
                    // Chained straight onto the search builder — see "Chaining Eloquent" in the package README.
                    $searchQuery->whereNotNull('email_verified_at');
                }

                $results = $searchQuery->get();
                $debugInfo = $searchQuery->getDebugInfo();
            } catch (\Ashiqfardus\LaravelFuzzySearch\Exceptions\InvalidAlgorithmException) {
                $algorithm = 'fuzzy';
            }
        }

        return view('search.users', [
            'results' => $results, 'query' => $query, 'algorithm' => $algorithm,
            'typoTolerance' => $typoTolerance, 'fallback' => $fallback,
            'verifiedOnly' => $verifiedOnly, 'debugInfo' => $debugInfo, 'attempts' => $attempts->all(),
        ]);
    }

    /**
     * Every search attempt — the primary algorithm and each fallback() retry — fires one
     * FuzzySearchExecuted event. Collect their algorithm names so the page can show which
     * one actually answered.
     */
    private function recordSearchAttempts(): \Illuminate\Support\Collection
    {
        $attempts = collect();
        Event::listen(FuzzySearchExecuted::class, function (FuzzySearchExecuted $event) use ($attempts) {
            $attempts->push($event->algorithm);
        });

        return $attempts;
    }

    /**
     * Search products with preset
     */
    public function products(Request $request)
    {
        $query    = $this->term($request);
        $preset   = $request->input('preset', 'ecommerce');
        $category = (string) $request->input('category', '');
        $engine   = $request->input('engine') === 'bm25' ? 'bm25' : 'like';

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $results    = collect();
        $attempts   = $this->recordSearchAttempts();

        if ($query) {
            $search = Product::search($query)
                ->preset($preset)
                ->withRelevance()
                ->highlight('mark')
                ->with('category');

            if ($category !== '') {
                // Honoured on both paths: on BM25 the ranking is checked against the
                // filter before the page is cut, so the page never comes back short.
                $search->filter('category_id', (int) $category);
            }

            if ($engine === 'bm25') {
                // The BM25 index is typo-tolerant on its own now; the fuzzy fallback only
                // answers when the index has no match at all (or has not been built for
                // products).
                $search->useInvertedIndex()->fallback('fuzzy');
            }

            $results = $search->get();
        }

        return view('search.products', [
            'results' => $results, 'query' => $query, 'preset' => $preset,
            'category' => $category, 'categories' => $categories,
            'engine' => $engine, 'attempts' => $attempts->all(),
        ]);
    }

    /**
     * Search articles with tokenization. The BM25 index answers by default (matchAll() holds
     * on it too); ?engine=like runs the blog preset's fuzzy LIKE patterns over title, body
     * and excerpt instead, which read every row.
     */
    public function articles(Request $request)
    {
        $query = $this->term($request);
        $matchMode = $request->input('match_mode', 'any');
        $engine = $this->engine($request);

        $results = collect();
        $attempts = $this->recordSearchAttempts();

        if ($query) {
            $searchQuery = Article::search($query)
                ->preset('blog')
                ->tokenize()
                ->withRelevance()
                ->highlight('mark');

            if ($matchMode === 'all') {
                $searchQuery->matchAll();
            } else {
                $searchQuery->matchAny();
            }

            if ($engine === 'bm25') {
                // As on products: the fuzzy fallback answers only when the index has no match
                // (or has not been built for articles).
                $searchQuery->useInvertedIndex()->fallback('fuzzy');
            }

            $results = $searchQuery->get();
        }

        return view('search.articles', [
            'results' => $results, 'query' => $query, 'matchMode' => $matchMode,
            'engine' => $engine, 'attempts' => $attempts->all(),
        ]);
    }

    /**
     * Search contacts with phonetic matching
     */
    public function contacts(Request $request)
    {
        $query = $this->term($request);
        $algorithm = $request->input('algorithm', 'soundex');

        $results = collect();

        if ($query) {
            try {
                $results = Contact::search($query)
                    ->using($algorithm)
                    ->withRelevance()
                    ->highlight('mark')
                    ->get();
            } catch (\Ashiqfardus\LaravelFuzzySearch\Exceptions\InvalidAlgorithmException) {
                $algorithm = 'soundex';
            }
        }

        return view('search.contacts', compact('results', 'query', 'algorithm'));
    }

    /**
     * Federated search across all models. FederatedSearch runs each model's own LIKE search
     * (it has no index path), and paginate() reads each table twice (count + page), so it
     * searches the short name columns only: email and the long product description made it
     * 1.7-1.9 s at 100k rows per model.
     */
    public function federated(Request $request)
    {
        $query = $this->term($request);

        $results = collect();

        if ($query) {
            $results = FederatedSearch::across([
                User::class,
                Product::class,
                Article::class,
                Contact::class,
            ])
                ->search($query)
                ->searchIn(['name', 'title', 'first_name', 'last_name'])
                ->limitPerModel(10)
                ->paginate(10)
                ->withQueryString();

            // FederatedSearch fetches each model separately, so the Article rows it returns
            // don't have author eager loaded — load it in one query rather than per row.
            \Illuminate\Database\Eloquent\Collection::make(
                $results->getCollection()->filter(fn ($item) => $item instanceof Article)
            )->load('author');
        }

        return view('search.federated', compact('results', 'query'));
    }

    /**
     * Relationship search: a match on author.name is scored and highlighted under the dotted
     * key. On the BM25 index (the default) the author's name is indexed with the article, so
     * it is a column-restricted index search; on the LIKE table scan (?engine=like) it
     * compiles to a whereHas/EXISTS clause on the Article query.
     */
    public function relations(Request $request)
    {
        $query    = $this->term($request);
        $engine   = $this->engine($request);
        $results  = collect();
        $attempts = $this->recordSearchAttempts();

        if ($query) {
            $search = Article::search($query)
                ->searchIn(['title' => 10, 'excerpt' => 3, 'author.name' => 6])
                ->withRelevance()
                ->highlight('mark');

            if ($engine === 'bm25') {
                $search->useInvertedIndex()->fallback('fuzzy');
            } else {
                $search->using('fuzzy');
            }

            $results = $search->paginate(10)->withQueryString();
        }

        return view('search.relations', [
            'results' => $results, 'query' => $query,
            'engine' => $engine, 'attempts' => $attempts->all(),
        ]);
    }

    /**
     * API endpoint for playground live search — the package's FuzzySearchCollection JSON
     * resource: each row's model attributes plus _score/_highlighted/etc, and meta
     * (query, algorithm, latency_ms, suggestions).
     */
    public function searchUsers(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $this->term($request);

        if (strlen($query) < 2 || strlen($query) > 200) {
            return response()->json(['data' => [], 'meta' => null]);
        }

        return FuzzySearchCollection::fromBuilder(
            User::search($query)->highlight('mark')->limit(10)
        )->response();
    }

    /**
     * API endpoint for autocomplete — every model completes from the BM25 dictionary
     * (suggest()) when it has been indexed, falling back to a table scan otherwise.
     */
    public function suggest(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $this->term($request);
        $model = $request->input('model', 'products');

        if (strlen($query) < 2 || strlen($query) > 200) {
            return response()->json(['suggestions' => [], 'source' => 'table', 'latency_ms' => 0.0]);
        }

        $modelClass = match ($model) {
            'users' => User::class,
            'articles' => Article::class,
            'contacts' => Contact::class,
            default => Product::class,
        };

        $searchColumns = match ($model) {
            'users'    => ['name', 'email'],
            'articles' => ['title', 'author.name'],
            'contacts' => ['first_name', 'last_name'],
            default    => ['name'],
        };

        $start = microtime(true);
        $suggestions = $modelClass::search($query)
            ->searchIn($searchColumns)
            ->suggest(8);
        $latencyMs = round((microtime(true) - $start) * 1000, 2);

        $source = DB::table('fuzzy_index_meta')->where('model_type', $modelClass)->exists() ? 'index' : 'table';

        return response()->json([
            'suggestions' => $suggestions,
            'source'      => $source,
            'latency_ms'  => $latencyMs,
        ]);
    }

    /**
     * As-you-type product search: prefix expansion of the last word against the BM25
     * dictionary (asYouType()), typo tolerance 1 (the default of 2 lets a four-letter prefix
     * such as "macb" expand into unrelated three-letter words like "tab"), and the
     * package's own JSON envelope so the payload shows scores, escaped highlights and meta.
     */
    public function asYouType(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $this->term($request);

        if (strlen($query) < 2 || strlen($query) > 200) {
            return response()->json(['data' => [], 'meta' => null]);
        }

        try {
            return FuzzySearchCollection::fromBuilder(
                Product::search($query)->useInvertedIndex()->asYouType()->typoTolerance(1)->highlight('mark')->limit(8)
            )->response();
        } catch (\Throwable $e) {
            logger()->error('as-you-type search failed', ['error' => $e->getMessage()]);

            return response()->json([
                'data' => [],
                'meta' => null,
                'error' => 'Index not available — run php artisan fuzzy-search:rebuild "App\Models\Product"',
            ]);
        }
    }

    /**
     * Side-by-side algorithm comparison
     */
    public function capabilityMatrix(): \Illuminate\View\View
    {
        $algorithms = ['simple', 'fuzzy', 'levenshtein', 'trigram', 'soundex', 'metaphone', 'similar_text'];
        $term       = request('q', 'john');
        $results    = [];

        if ($term) {
            foreach ($algorithms as $algo) {
                $start = microtime(true);
                try {
                    $items = User::search($term)
                        ->using($algo)
                        ->withRelevance()
                        ->take(5)
                        ->get();
                    $ms = round((microtime(true) - $start) * 1000, 2);
                    $results[$algo] = ['rows' => $items, 'error' => null, 'ms' => $ms];
                } catch (\Throwable $e) {
                    $ms = round((microtime(true) - $start) * 1000, 2);
                    logger()->error('capability-matrix search failed', ['algo' => $algo, 'error' => $e->getMessage()]);
                    $results[$algo] = ['rows' => collect(), 'error' => 'Search unavailable', 'ms' => $ms];
                }
            }
        }

        return view('search.capability-matrix', compact('algorithms', 'term', 'results'));
    }

    /**
     * Smart search features: Did You Mean, Suggestions, Autocomplete. On the BM25 index (the
     * default) each model is one typo-tolerant index search and "Did you mean" reads the index
     * dictionary (didYouMean()); on the LIKE table scan (?engine=like) each model runs
     * levenshtein and fuzzy over every row.
     */
    public function smart(Request $request)
    {
        $query = $this->term($request);
        $engine = $this->engine($request);

        $results = collect();
        $suggestions = [];
        $didYouMean = [];
        $autocomplete = [];

        if ($query) {
            // A model whose index is not built yet is searched with fuzzy LIKE instead. Not
            // fallback('fuzzy'): a term usually lives in one or two of the four models, so a
            // fallback on every miss would scan the other tables on almost every search.
            $indexed = $engine === 'bm25' ? DB::table('fuzzy_index_meta')->pluck('model_type')->all() : [];

            // Helper function to search and map results with similarity
            $searchModel = function($modelClass, $type, $nameField) use ($query, $engine, $indexed) {
                if ($engine === 'bm25') {
                    $search = $modelClass::search($query)
                        ->typoTolerance(2)
                        ->withRelevance()
                        ->highlight('mark');

                    if (in_array($modelClass, $indexed, true)) {
                        $search->useInvertedIndex();
                    } else {
                        $search->using('fuzzy');
                    }

                    $merged = $search->get();
                } else {
                    // Try levenshtein first (better for typos)
                    $levenResults = $modelClass::search($query)
                        ->using('levenshtein')
                        ->typoTolerance(3)
                        ->withRelevance()
                        ->highlight('mark')
                        ->get();

                    // Also try fuzzy for broader matches
                    $fuzzyResults = $modelClass::search($query)
                        ->using('fuzzy')
                        ->typoTolerance(4)
                        ->withRelevance()
                        ->highlight('mark')
                        ->get();

                    // Merge and deduplicate
                    $merged = $levenResults->merge($fuzzyResults)->unique('id');
                }

                return $merged->map(function($item) use ($query, $type, $nameField) {
                    $similarity = 0;
                    $name = is_array($nameField)
                        ? $item->{$nameField[0]}
                        : $item->{$nameField};
                    similar_text(strtolower($query), strtolower($name), $similarity);
                    $item->_score = max($item->_score ?? 0, $similarity);
                    return ['type' => $type, 'item' => $item, 'similarity' => $similarity];
                });
            };

            $productResults = $searchModel(Product::class, 'Product', 'name');
            $userResults = $searchModel(User::class, 'User', 'name');
            $articleResults = $searchModel(Article::class, 'Article', 'title');
            $contactResults = $searchModel(Contact::class, 'Contact', 'first_name');

            // Combine and sort by similarity score (higher = better match)
            $results = $productResults
                ->concat($userResults)
                ->concat($articleResults)
                ->concat($contactResults)
                ->sortByDesc('similarity')
                ->take(20)
                ->values();

            // Get autocomplete/suggestions from actual matches
            $autocomplete = $results->take(8)->map(function($r) {
                $item = $r['item'];
                $type = $r['type'];
                return match($type) {
                    'Product' => $item->name,
                    'User' => $item->name,
                    'Article' => $item->title,
                    'Contact' => $item->first_name . ' ' . $item->last_name,
                    default => null
                };
            })->filter()->unique()->values()->toArray();

            if ($engine === 'bm25') {
                // "Did you mean" from the index dictionary: each model's own words within a few
                // edits of the query, closest first, then the most confident.
                $didYouMean = collect([Product::class, User::class, Article::class, Contact::class])
                    ->flatMap(fn ($modelClass) => array_map(
                        fn ($alternative) => $alternative + ['type' => class_basename($modelClass)],
                        $modelClass::search($query)->didYouMean(3)
                    ))
                    ->sortBy([['distance', 'asc'], ['confidence', 'desc']])
                    ->unique('term')
                    ->take(3)
                    ->values()
                    ->toArray();
            } else {
                // Generate "Did You Mean" from top results if query doesn't match exactly
                $didYouMean = $results->take(3)->map(function($r) {
                    $item = $r['item'];
                    $type = $r['type'];
                    $term = match($type) {
                        'Product' => $item->name,
                        'User' => $item->name,
                        'Article' => $item->title,
                        'Contact' => $item->first_name,
                        default => null
                    };
                    return ['term' => $term, 'type' => $type];
                })->filter(fn($d) => $d['term'] !== null)->values()->toArray();
            }

            // Suggestions - same as autocomplete for now
            $suggestions = collect($autocomplete)->map(fn($s) => ['text' => $s])->toArray();
        }

        return view('search.smart', compact('results', 'query', 'engine', 'suggestions', 'didYouMean', 'autocomplete'));
    }

    /**
     * Persisted search analytics — popular terms, zero-result terms, latency by path and
     * daily volume, all read from fuzzy_search_logs via the SearchAnalytics facade.
     */
    public function analytics(): \Illuminate\View\View
    {
        return view('search.analytics', [
            'popular' => SearchAnalytics::popular(30, 10),
            'zero'    => SearchAnalytics::zeroResults(30, 10),
            'latency' => SearchAnalytics::averageLatency(30),
            'volume'  => SearchAnalytics::volume(30),
        ]);
    }

    /**
     * CJK search: the script-aware tokenizer cuts character bigrams for Chinese, Japanese
     * and Korean runs (and leaves whitespace-delimited scripts alone), so product names
     * with no word boundaries are searchable through the same BM25 index as everything else.
     */
    public function i18n(): \Illuminate\View\View
    {
        $query = request('q', '東京');

        $search = Product::search($query)
            ->useInvertedIndex()
            ->withRelevance()
            ->highlight('mark')
            ->limit(20);

        $results = $search->get();

        return view('search.i18n', [
            'results'    => $results,
            'query'      => $query,
            'indexTerms' => $search->getDebugInfo()['index_terms'],
        ]);
    }

    /**
     * LIKE vs BM25 side-by-side benchmark. `typo` (0–3) adds a third panel that runs the
     * same index query with typo expansion on, so the latency cost of dictionary expansion
     * is visible next to the exact-term index read.
     */
    public function benchmarks(): \Illuminate\View\View
    {
        $term    = request('q', 'john');
        $typo    = max(0, min(3, (int) request('typo', 0)));
        $results = [];

        if ($term) {
            // LIKE-based path
            $start = microtime(true);
            $likeResults = User::search($term)->withRelevance()->take(5)->get();
            $results['like'] = [
                'rows' => $likeResults,
                'ms'   => round((microtime(true) - $start) * 1000, 2),
                'label' => 'LIKE Pattern Search',
            ];

            // BM25 inverted index path — exact dictionary terms
            $results['bm25'] = $this->benchmarkIndex($term, 0, 'BM25 Inverted Index');

            // Same index read with typo expansion on (TermExpander widens each term)
            if ($typo > 0) {
                $results['bm25_typo'] = $this->benchmarkIndex($term, $typo, 'BM25 + typo tolerance');
            }
        }

        return view('search.benchmarks', compact('term', 'typo', 'results'));
    }

    /**
     * One timed BM25 index read. A missing index is reported in the panel instead of
     * throwing, so the page still renders before the first rebuild.
     */
    private function benchmarkIndex(string $term, int $typoTolerance, string $label): array
    {
        $start = microtime(true);

        try {
            $rows = User::search($term)->useInvertedIndex()->typoTolerance($typoTolerance)->take(5)->get();

            return [
                'rows'  => $rows,
                'ms'    => round((microtime(true) - $start) * 1000, 2),
                'label' => $label,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            logger()->error('BM25 benchmark failed', ['label' => $label, 'error' => $e->getMessage()]);

            return [
                'rows'  => collect(),
                'ms'    => 0,
                'label' => $label,
                'error' => 'Index not available — run php artisan fuzzy-search:rebuild "App\Models\User"',
            ];
        }
    }

    /**
     * Extended-search syntax playground
     */
    public function playground(): \Illuminate\View\View
    {
        $query = request('q', "^John Doe !banned");

        $ast     = null;
        $error   = null;
        $results = collect();

        if ($query) {
            try {
                $tokens = (new \Ashiqfardus\LaravelFuzzySearch\Query\Lexer())->tokenize($query);
                $ast    = (new \Ashiqfardus\LaravelFuzzySearch\Query\ExtendedQueryParser())->parse($tokens);

                $results = User::search($query)->extended()->paginate(10)->withQueryString();
            } catch (\Ashiqfardus\LaravelFuzzySearch\Exceptions\QuerySyntaxException $e) {
                $error = $e->getMessage();
            } catch (\Throwable $e) {
                logger()->error('Playground search failed', ['error' => $e->getMessage()]);
                $error = 'Search unavailable — please try a different query.';
            }
        }

        return view('search.playground', compact('query', 'ast', 'error', 'results'));
    }

    /**
     * Scout driver demo page
     */
    public function scoutDemo(): \Illuminate\View\View
    {
        $term    = request('q', '');
        $results = collect();

        if ($term) {
            try {
                $results = User::search($term)->withRelevance()->take(10)->get();
            } catch (\Throwable) {
                $results = collect();
            }
        }

        return view('search.scout-demo', compact('term', 'results'));
    }

    /** The q parameter as a string — an array (?q[]=x) or a missing value reads as ''. */
    private function term(Request $request): string
    {
        $q = $request->input('q', '');

        return is_string($q) ? $q : '';
    }

    /**
     * The engine a page searches with: the BM25 index by default, the LIKE table scan only
     * when ?engine=like asks for it. The scan reads every row, so it is slow on a large table.
     */
    private function engine(Request $request): string
    {
        return $request->input('engine') === 'like' ? 'like' : 'bm25';
    }
}
