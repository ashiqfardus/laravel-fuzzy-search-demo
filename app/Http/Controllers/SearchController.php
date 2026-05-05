<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Article;
use App\Models\Contact;
use Ashiqfardus\LaravelFuzzySearch\FederatedSearch;

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
        $query = $request->input('q', '');
        $algorithm = $request->input('algorithm', 'fuzzy');
        $typoTolerance = (int) $request->input('typo_tolerance', 2);

        $results = collect();
        $debugInfo = [];

        if ($query) {
            try {
                $searchQuery = User::search($query)
                    ->using($algorithm)
                    ->typoTolerance($typoTolerance)
                    ->withRelevance()
                    ->highlight('mark');

                $results = $searchQuery->get();
                $debugInfo = $searchQuery->getDebugInfo();
            } catch (\Ashiqfardus\LaravelFuzzySearch\Exceptions\InvalidAlgorithmException) {
                $algorithm = 'fuzzy';
            }
        }

        return view('search.users', compact('results', 'query', 'algorithm', 'typoTolerance', 'debugInfo'));
    }

    /**
     * Search products with preset
     */
    public function products(Request $request)
    {
        $query = $request->input('q', '');
        $preset = $request->input('preset', 'ecommerce');

        $results = collect();

        if ($query) {
            $results = Product::search($query)
                ->preset($preset)
                ->withRelevance()
                ->highlight('mark')
                ->get();
        }

        return view('search.products', compact('results', 'query', 'preset'));
    }

    /**
     * Search articles with tokenization
     */
    public function articles(Request $request)
    {
        $query = $request->input('q', '');
        $matchMode = $request->input('match_mode', 'any');

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

            $results = $searchQuery->get();
        }

        return view('search.articles', compact('results', 'query', 'matchMode'));
    }

    /**
     * Search contacts with phonetic matching
     */
    public function contacts(Request $request)
    {
        $query = $request->input('q', '');
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
     * Federated search across all models
     */
    public function federated(Request $request)
    {
        $query = $request->input('q', '');

        $results = collect();

        if ($query) {
            $results = FederatedSearch::across([
                User::class,
                Product::class,
                Article::class,
                Contact::class,
            ])
                ->search($query)
                ->searchIn(['name', 'title', 'first_name', 'email', 'description'])
                ->limit(20)
                ->get();
        }

        return view('search.federated', compact('results', 'query'));
    }

    /**
     * API endpoint for playground live search — returns full User records as JSON
     */
    public function searchUsers(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2 || strlen($query) > 200) {
            return response()->json([]);
        }

        $safe = addcslashes($query, '%_');
        $results = User::where('name', 'LIKE', '%' . $safe . '%')
            ->orWhere('email', 'LIKE', '%' . $safe . '%')
            ->limit(8)
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]);

        return response()->json($results);
    }

    /**
     * API endpoint for autocomplete
     */
    public function suggest(Request $request)
    {
        $query = $request->input('q', '');
        $model = $request->input('model', 'products');

        if (strlen($query) < 2 || strlen($query) > 200) {
            return response()->json([]);
        }

        $modelClass = match($model) {
            'users' => User::class,
            'articles' => Article::class,
            'contacts' => Contact::class,
            default => Product::class,
        };

        $searchColumns = match($model) {
            'users'    => ['name', 'email'],
            'articles' => ['title', 'author'],
            'contacts' => ['first_name', 'last_name'],
            default    => ['name'],
        };

        $suggestions = $modelClass::search($query)
            ->searchIn($searchColumns)
            ->suggest(5);

        return response()->json($suggestions);
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
     * Smart search features: Did You Mean, Suggestions, Autocomplete
     */
    public function smart(Request $request)
    {
        $query = $request->input('q', '');

        $results = collect();
        $suggestions = [];
        $didYouMean = [];
        $autocomplete = [];

        if ($query) {
            // Helper function to search and map results with similarity
            $searchModel = function($modelClass, $type, $nameField) use ($query) {
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

            // Suggestions - same as autocomplete for now
            $suggestions = collect($autocomplete)->map(fn($s) => ['text' => $s])->toArray();
        }

        return view('search.smart', compact('results', 'query', 'suggestions', 'didYouMean', 'autocomplete'));
    }

    /**
     * LIKE vs BM25 side-by-side benchmark
     */
    public function benchmarks(): \Illuminate\View\View
    {
        $term    = request('q', 'john');
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

            // BM25 inverted index path
            $start = microtime(true);
            try {
                $bm25Results = User::search($term)->useInvertedIndex()->take(5)->get();
                $results['bm25'] = [
                    'rows'  => $bm25Results,
                    'ms'    => round((microtime(true) - $start) * 1000, 2),
                    'label' => 'BM25 Inverted Index',
                    'error' => null,
                ];
            } catch (\Throwable $e) {
                logger()->error('BM25 benchmark failed', ['error' => $e->getMessage()]);
                $results['bm25'] = ['rows' => collect(), 'ms' => 0, 'label' => 'BM25', 'error' => 'Index not available — run php artisan fuzzy-search:rebuild'];
            }
        }

        return view('search.benchmarks', compact('term', 'results'));
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

                $results = User::search($query)->extended()->take(10)->get();
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
}
