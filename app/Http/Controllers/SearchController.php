<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
     * How many rows of a model a LIKE showcase reads: the capability matrix and the playground
     * (users), and smart search's LIKE mode (each model). Over 1M users, seven LIKE algorithms
     * over the whole table took 6.7-7.8 s; smart's eight whole-table scans took 29 s.
     */
    private const LIKE_SLICE = 10000;

    /** The algorithms the users page's select offers, its default first. */
    private const USER_ALGORITHMS = ['fuzzy', 'levenshtein', 'soundex', 'metaphone', 'simple'];

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
     * Search users with various options. The BM25 index answers by default once users are
     * indexed (typo tolerance and the verified-only filter hold on it); the algorithm and the
     * fallback chain are the LIKE table scan's (?engine=like), which also answers while the
     * users index is not built. Not fallback() on the index: at 1M users a miss would then
     * scan the table, twice with a LIKE fallback chain (5 s).
     */
    public function users(Request $request)
    {
        $query = $this->term($request);
        $engine = $this->engine($request);
        $algorithm = $this->option($request, 'algorithm', self::USER_ALGORITHMS);
        $typoTolerance = max(0, min(5, (int) $request->input('typo_tolerance', 2)));
        $fallback = $request->input('fallback', '');
        if (!in_array($fallback, self::USER_ALGORITHMS, true) || $fallback === $algorithm) {
            $fallback = '';
        }
        $verifiedOnly = $request->boolean('verified_only');
        $useIndex = $engine === 'bm25' && $this->isIndexed(User::class);

        $results = collect();
        $debugInfo = [];
        $attempts = $this->recordSearchAttempts();

        if ($query) {
            $searchQuery = User::search($query)
                ->typoTolerance($typoTolerance)
                ->withRelevance()
                ->highlight('mark');

            if ($useIndex) {
                $searchQuery->useInvertedIndex();
            } else {
                $searchQuery->using($algorithm);

                if ($fallback !== '') {
                    // Re-runs the search with this algorithm only if the primary finds nothing.
                    $searchQuery->fallback($fallback);
                }
            }

            if ($verifiedOnly) {
                // Chained straight onto the search builder — see "Chaining Eloquent" in the package README.
                $searchQuery->whereNotNull('email_verified_at');
            }

            $results = $searchQuery->paginate(10)->withQueryString();
            $debugInfo = $searchQuery->getDebugInfo();
        }

        return view('search.users', [
            'results' => $results, 'query' => $query, 'engine' => $engine, 'useIndex' => $useIndex, 'algorithm' => $algorithm,
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
     * Search products with preset. The BM25 index answers by default once products are
     * indexed; ?engine=like, or an index not built yet, runs the LIKE table scan, where the
     * preset picks the algorithm (its typo tolerance holds on the index too). No fallback()
     * on the index: a miss would then scan the table (6.2-7.8 s at 1M products), so the
     * empty page links to the LIKE scan instead.
     */
    public function products(Request $request)
    {
        $query    = $this->term($request);
        $preset   = $this->option($request, 'preset', ['ecommerce', 'exact']);
        $category = $this->stringInput($request, 'category', '');
        $engine   = $this->engine($request);
        $useIndex = $engine === 'bm25' && $this->isIndexed(Product::class);

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $results    = collect();

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

            if ($useIndex) {
                $search->useInvertedIndex();
            }

            $results = $search->paginate(10)->withQueryString();
        }

        return view('search.products', [
            'results' => $results, 'query' => $query, 'preset' => $preset,
            'category' => $category, 'categories' => $categories,
            'engine' => $engine, 'useIndex' => $useIndex,
        ]);
    }

    /**
     * Search articles with tokenization. The BM25 index answers by default once articles are
     * indexed (matchAll() holds on it too); ?engine=like, or an index not built yet, runs the
     * blog preset's fuzzy LIKE patterns over title, body and excerpt, which read every row.
     * No fallback() on the index: a miss would then scan the table (2.2-2.9 s at 100k), so
     * the empty page links to the LIKE scan instead.
     */
    public function articles(Request $request)
    {
        $query = $this->term($request);
        $matchMode = $request->input('match_mode', 'any');
        $engine = $this->engine($request);
        $useIndex = $engine === 'bm25' && $this->isIndexed(Article::class);

        $results = collect();

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

            if ($useIndex) {
                $searchQuery->useInvertedIndex();
            }

            $results = $searchQuery->paginate(10)->withQueryString();
        }

        return view('search.articles', [
            'results' => $results, 'query' => $query, 'matchMode' => $matchMode,
            'engine' => $engine, 'useIndex' => $useIndex,
        ]);
    }

    /**
     * Search contacts with phonetic matching
     */
    public function contacts(Request $request)
    {
        $query = $this->term($request);
        // Contact has no metaphone shadow columns: an algorithm the select does not offer is
        // read as Soundex, never passed on (?algorithm=metaphone threw).
        $algorithm = $this->option($request, 'algorithm', ['soundex', 'levenshtein', 'fuzzy', 'similar_text']);

        $results = collect();

        if ($query) {
            $results = Contact::search($query)
                ->using($algorithm)
                ->withRelevance()
                ->highlight('mark')
                ->paginate(10)->withQueryString();
        }

        // MySQL/MariaDB run SOUNDEX() natively; the others take the package's phonetic pattern
        // fallback, which matches spellings, so the page's examples differ.
        $nativeSoundex = in_array((new Contact())->getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        return view('search.contacts', compact('results', 'query', 'algorithm', 'nativeSoundex'));
    }

    /**
     * Federated search across all models. Once every model is indexed, each is searched
     * through its BM25 index (FederatedSearch::useInvertedIndex()) on its own configured
     * columns. While any index is missing, each model runs its own LIKE search instead, and
     * paginate() reads each table twice (count + page), so that scan searches the short name
     * columns only: email and the long product description made it 1.7-1.9 s at 100k rows
     * per model.
     */
    public function federated(Request $request)
    {
        $query = $this->term($request);
        $models = [User::class, Product::class, Article::class, Contact::class];

        $results = collect();
        $missing = [];

        if ($query) {
            $missing = array_values(array_filter($models, fn (string $model) => !$this->isIndexed($model)));
            $search = FederatedSearch::across($models)->search($query)->limitPerModel(10);

            if ($missing === []) {
                $search->useInvertedIndex();
            } else {
                $search->searchIn(['name', 'title', 'first_name', 'last_name']);
            }

            $results = $search->paginate(10)->withQueryString();

            // FederatedSearch fetches each model separately, so the Article rows it returns
            // don't have author eager loaded — load it in one query rather than per row.
            \Illuminate\Database\Eloquent\Collection::make(
                $results->getCollection()->filter(fn ($item) => $item instanceof Article)
            )->load('author');
        }

        return view('search.federated', compact('results', 'query', 'missing'));
    }

    /**
     * Relationship search: a match on author.name is scored and highlighted under the dotted
     * key. On the BM25 index (the default, once articles are indexed) the author's name is
     * indexed with the article, so it is a column-restricted index search; on the LIKE table
     * scan (?engine=like, or an index not built yet) it compiles to a whereHas/EXISTS clause
     * on the Article query. No fallback() on the index: a miss would then scan the table
     * (2.7-4.5 s at 100k), so the empty page links to the LIKE scan instead.
     */
    public function relations(Request $request)
    {
        $query    = $this->term($request);
        $engine   = $this->engine($request);
        $useIndex = $engine === 'bm25' && $this->isIndexed(Article::class);
        $results  = collect();

        if ($query) {
            $search = Article::search($query)
                ->searchIn(['title' => 10, 'excerpt' => 3, 'author.name' => 6])
                ->withRelevance()
                ->highlight('mark');

            if ($useIndex) {
                $search->useInvertedIndex();
            } else {
                $search->using('fuzzy');
            }

            $results = $search->paginate(10)->withQueryString();
        }

        return view('search.relations', [
            'results' => $results, 'query' => $query,
            'engine' => $engine, 'useIndex' => $useIndex,
        ]);
    }

    /**
     * API endpoint for playground live search — the package's FuzzySearchCollection JSON
     * resource: each row's model attributes plus _score/_highlighted/etc, and meta
     * (query, algorithm, latency_ms, suggestions). It reads the BM25 index once users are
     * indexed: a LIKE scan per keystroke took 2.26 s at 1M users.
     */
    public function searchUsers(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $this->term($request);

        if (mb_strlen($query) < 2 || mb_strlen($query) > 200) {
            return response()->json(['data' => [], 'meta' => null]);
        }

        $search = User::search($query)->highlight('mark')->limit(10);

        if ($this->isIndexed(User::class)) {
            $search->useInvertedIndex();
        }

        return FuzzySearchCollection::fromBuilder($search)->response();
    }

    /**
     * API endpoint for autocomplete — every model completes from the BM25 dictionary
     * (suggest()) when it has been indexed, falling back to a table scan otherwise.
     */
    public function suggest(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $this->term($request);
        $model = $request->input('model', 'products');

        if (mb_strlen($query) < 2 || mb_strlen($query) > 200) {
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

        if (mb_strlen($query) < 2 || mb_strlen($query) > 200) {
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
     * Side-by-side algorithm comparison, run over the first LIKE_SLICE users so every
     * algorithm answers quickly however large the table grows.
     */
    public function capabilityMatrix(): \Illuminate\View\View
    {
        $algorithms = ['simple', 'fuzzy', 'levenshtein', 'trigram', 'soundex', 'metaphone', 'similar_text'];
        $term       = $this->term(request(), 'john');
        $results    = [];
        $slice      = self::LIKE_SLICE;

        if ($term) {
            $ceiling = $this->sliceCeiling(User::class);

            foreach ($algorithms as $algo) {
                $start = microtime(true);
                try {
                    $search = User::search($term)
                        ->using($algo)
                        ->withRelevance()
                        ->take(5);

                    if ($ceiling !== null) {
                        $search->where('id', '<=', $ceiling);
                    }

                    $items = $search->get();
                    $ms = round((microtime(true) - $start) * 1000, 2);
                    $results[$algo] = ['rows' => $items, 'error' => null, 'ms' => $ms];
                } catch (\Throwable $e) {
                    $ms = round((microtime(true) - $start) * 1000, 2);
                    logger()->error('capability-matrix search failed', ['algo' => $algo, 'error' => $e->getMessage()]);
                    $results[$algo] = ['rows' => collect(), 'error' => 'Search unavailable', 'ms' => $ms];
                }
            }
        }

        return view('search.capability-matrix', compact('algorithms', 'term', 'results', 'slice'));
    }

    /**
     * The key of the model's LIKE_SLICE-th row by key, or null when the table holds fewer. A
     * LIKE showcase reads rows up to it, a primary-key range, so each of its scans reads at
     * most LIKE_SLICE rows.
     */
    private function sliceCeiling(string $modelClass): ?int
    {
        $keyName = (new $modelClass())->getKeyName();
        $key = $modelClass::query()->orderBy($keyName)->skip(self::LIKE_SLICE - 1)->value($keyName);

        return $key === null ? null : (int) $key;
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
                    // Both passes read only the model's first LIKE_SLICE rows, by a primary-key
                    // bound: eight whole-table scans took 29 s at 1M rows, past PHP's 30 s limit.
                    $ceiling = $this->sliceCeiling($modelClass);
                    $sliced = function ($search) use ($modelClass, $ceiling) {
                        if ($ceiling !== null) {
                            $search->where((new $modelClass())->getQualifiedKeyName(), '<=', $ceiling);
                        }

                        return $search->get();
                    };

                    // Try levenshtein first (better for typos)
                    $levenResults = $sliced($modelClass::search($query)
                        ->using('levenshtein')
                        ->typoTolerance(3)
                        ->withRelevance()
                        ->highlight('mark'));

                    // Also try fuzzy for broader matches
                    $fuzzyResults = $sliced($modelClass::search($query)
                        ->using('fuzzy')
                        ->typoTolerance(4)
                        ->withRelevance()
                        ->highlight('mark'));

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

            // Merge by name similarity (higher = better match): each model's relevance scores
            // are on its own scale, so they cannot order rows across models.
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

        $slice = self::LIKE_SLICE;

        return view('search.smart', compact('results', 'query', 'engine', 'slice', 'suggestions', 'didYouMean', 'autocomplete'));
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
        $query = $this->term(request(), '東京');
        $results = collect();
        $indexTerms = [];

        if ($query !== '') {
            $search = Product::search($query)
                ->useInvertedIndex()
                ->withRelevance()
                ->highlight('mark')
                ->limit(20);

            $results = $search->get();
            $indexTerms = $search->getDebugInfo()['index_terms'];
        }

        return view('search.i18n', [
            'results'    => $results,
            'query'      => $query,
            'indexTerms' => $indexTerms,
        ]);
    }

    /**
     * LIKE vs BM25 side-by-side benchmark. `typo` (0–3) adds a third panel that runs the
     * same index query with typo expansion on, so the latency cost of dictionary expansion
     * is visible next to the exact-term index read.
     */
    public function benchmarks(): \Illuminate\View\View
    {
        $term    = $this->term(request(), 'john');
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

        $indexed = $this->isIndexed(User::class);

        return view('search.benchmarks', compact('term', 'typo', 'results', 'indexed'));
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
     * Extended-search syntax playground. extended() compiles to LIKE (it has no index path),
     * so it runs over the first LIKE_SLICE users, like the capability matrix.
     */
    public function playground(): \Illuminate\View\View
    {
        $query = $this->term(request(), "^John Doe !banned");
        $slice = self::LIKE_SLICE;

        $ast     = null;
        $error   = null;
        $results = collect();

        if ($query) {
            try {
                // The package's @internal lexer and parser: the demo is first-party and moves with
                // the package (PlaygroundAstTest fails if they change shape).
                $tokens = (new \Ashiqfardus\LaravelFuzzySearch\Query\Lexer())->tokenize($query);
                $ast    = $this->astTree((new \Ashiqfardus\LaravelFuzzySearch\Query\ExtendedQueryParser())->parse($tokens));

                $search = User::search($query)->extended();

                if (($ceiling = $this->sliceCeiling(User::class)) !== null) {
                    $search->where('id', '<=', $ceiling);
                }

                $results = $search->paginate(10)->withQueryString();
            } catch (\Ashiqfardus\LaravelFuzzySearch\Exceptions\QuerySyntaxException $e) {
                $error = $e->getMessage();
            } catch (\Throwable $e) {
                logger()->error('Playground search failed', ['error' => $e->getMessage()]);
                $error = 'Search unavailable — please try a different query.';
            }
        }

        return view('search.playground', compact('query', 'ast', 'error', 'results', 'slice'));
    }

    /**
     * The parsed query for the AST panel: each node's type and the operator it was read from
     * (a plain word or quoted phrase has none), then its fields, children mapped the same way.
     * Each node is a class with public properties only, so json_encode() alone drops the type.
     */
    private function astTree(\Ashiqfardus\LaravelFuzzySearch\Query\AstNodes\AstNode $node): array
    {
        $type = class_basename($node);
        $operator = ['AndNode' => 'AND', 'OrNode' => '|', 'NotNode' => '!', 'ExactTerm' => '=', 'PrefixTerm' => '^',
            'SuffixTerm' => '$', 'IncludeMatchTerm' => "'", 'TypoTerm' => '~', 'FieldTerm' => 'field:'][$type] ?? null;

        $tree = ['type' => $type] + ($operator === null ? [] : ['operator' => $operator]);
        foreach (get_object_vars($node) as $key => $value) {
            $tree[$key] = match (true) {
                $value instanceof \Ashiqfardus\LaravelFuzzySearch\Query\AstNodes\AstNode => $this->astTree($value),
                is_array($value) => array_map(fn ($child) => $this->astTree($child), $value),
                default => $value,
            };
        }

        return $tree;
    }

    /**
     * Scout driver demo page. The Scout engine wraps the BM25 index, so the page reads it too
     * (the users fuzzy LIKE scan it ran took 2.26 s at 1M users); before the users index is
     * built it finds nothing, and the page says to rebuild.
     */
    public function scoutDemo(): \Illuminate\View\View
    {
        $term    = $this->term(request());
        $results = collect();

        if ($term) {
            try {
                $results = User::search($term)->useInvertedIndex()->withRelevance()->take(10)->get();
            } catch (\Throwable) {
                $results = collect();
            }
        }

        return view('search.scout-demo', compact('term', 'results'));
    }

    /** The q parameter as a string: a missing one reads as $default, an empty one or an array (?q[]=x) as ''. */
    private function term(Request $request, string $default = ''): string
    {
        $q = $request->input('q', $default);

        return is_string($q) ? $q : '';
    }

    /** A select's parameter as a string: a missing or empty one, or an array (?x[]=1), reads as $default. */
    private function stringInput(Request $request, string $key, string $default): string
    {
        $value = $request->input($key, $default);

        return is_string($value) ? $value : $default;
    }

    /** A select's parameter: one of the values it offers, else the first (its default). */
    private function option(Request $request, string $key, array $options): string
    {
        $value = $request->input($key);

        return in_array($value, $options, true) ? $value : $options[0];
    }

    /**
     * Whether the model's BM25 index is built: fuzzy-search:rebuild writes its meta row, and the
     * index holds a document for every row. A partial index (reindexRelated() before the first
     * rebuild, or rows added while indexing was off) would answer with missing rows, so it
     * counts as not built and the page offers the LIKE scan and the rebuild command.
     */
    private function isIndexed(string $modelClass): bool
    {
        $docs = DB::table('fuzzy_index_meta')->where('model_type', $modelClass)->value('total_docs');

        if ($docs === null) {
            return false;
        }

        // The row count is cached per model and meta total_docs: an index write changes
        // total_docs and re-checks at once. Ceiling: rows added while indexing is off are
        // noticed within 60 s, not instantly. (COUNT(*) per model per request is too slow at 1M rows.)
        return Cache::remember("demo:indexed:{$modelClass}:{$docs}", 60, fn () => (int) $docs >= $modelClass::query()->count());
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
