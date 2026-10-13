# Laravel Fuzzy Search Demo

A demonstration application showcasing the [ashiqfardus/laravel-fuzzy-search](https://github.com/ashiqfardus/laravel-fuzzy-search) package — **v2.1.0**.

## Links

- **Package:** [ashiqfardus/laravel-fuzzy-search](https://github.com/ashiqfardus/laravel-fuzzy-search)
- **Packagist:** [packagist.org/packages/ashiqfardus/laravel-fuzzy-search](https://packagist.org/packages/ashiqfardus/laravel-fuzzy-search)

## Features Demonstrated

| Page | What it shows |
| ---- | ------------- |
| `/search/users` | 8 algorithms, typo tolerance, relevance scoring, `@fuzzyHighlight`, `fallback()` chain with an "answered by" trace, `verified_only` toggle chaining `->whereNotNull('email_verified_at')` straight onto the search builder |
| `/search/products` | Config presets (`ecommerce`, `exact`), weighted BM25 (`name` weight 10 outranks `description` weight 5 — try `macbook`: the MacBook is the title match, the Dell XPS and iPad only mention it), `category_id` `filter()` honoured on the BM25 path, typo-tolerant BM25 → fuzzy fallback, plus an as-you-type box calling `/api/as-you-type` — `useInvertedIndex()->asYouType()` prefix-expands the last word against the dictionary and returns the package's `FuzzySearchCollection` |
| `/search/relations` | `searchIn(['author.name' => 6])`: the relation match is scored and highlighted under the dotted key — try `smith` (author "John Smith" is in the fixed seeded pool). **Engine:** the BM25 index by default (`useInvertedIndex()->fallback('fuzzy')`), which holds the author's name with each article, so the dotted column is a column-restricted index search; the fuzzy LIKE scan reads every article (2.7–4.5 s at 100k), so it is the labelled opt-in `?engine=like`, where the dotted column compiles to a `whereHas`/EXISTS clause |
| `/search/articles` | Multi-word tokenization with AND / OR logic (`matchAll()` holds on the index too), author shown via the `Author` relation. **Engine:** the BM25 index by default (`useInvertedIndex()->fallback('fuzzy')`), because the blog preset's LIKE patterns read every article's long `body` (2.2–2.9 s at 100k articles); `?engine=like` keeps that table scan as a labelled opt-in |
| `/search/contacts` | Phonetic search — Soundex & Metaphone shadow columns |
| `/search/federated` | Cross-model federated search with unified ranking, `limitPerModel(10)->paginate(10)` with a reachable-rows total and a dark-theme pager |
| `/search/smart` | `didYouMean()`, autocomplete suggestions, in-memory search, plus a live autocomplete widget calling `/api/suggest?model=users&q=` — dictionary-backed (`suggest()`) when the model is indexed, table scan otherwise |
| `/search/benchmarks` | Side-by-side timing: LIKE vs the BM25 inverted index, with a typo-tolerance toggle that adds a third panel running the same index read with each term expanded by edit distance |
| `/search/capability-matrix` | Feature availability per algorithm and database driver |
| `/search/scout-demo` | Laravel Scout integration (`SCOUT_DRIVER=fuzzy-search`) |
| `/search/playground` | Extended query syntax: include `'`, exact `=`, prefix `^`, suffix `$`, exclude `!`, OR, grouping `()`, typo `~word`, field scope `field:term`; `extended()->paginate(10)` with the same pager component, plus a live preview calling `/api/search/users` that returns the package's `FuzzySearchCollection` — scores, escaped highlights and search meta (algorithm, latency) |
| `/search/analytics` | Popular and zero-result searches, latency by path and daily volume, read from `fuzzy_search_logs` via the `SearchAnalytics` facade |
| `/search/i18n` | Chinese, Japanese and Korean product names through the `ScriptAwareTokenizer` — character bigrams for CJK runs, whitespace for everything else, same BM25 index |

## Installation

```bash
git clone https://github.com/ashiqfardus/laravel-fuzzy-search-demo.git
cd laravel-fuzzy-search-demo

composer install
cp .env.example .env
php artisan key:generate

php artisan migrate
php artisan db:seed

# (Optional) Build the BM25 inverted index for benchmark and Scout pages
php artisan fuzzy-search:rebuild "App\Models\User"
php artisan fuzzy-search:rebuild "App\Models\Product"
php artisan fuzzy-search:rebuild "App\Models\Article"
php artisan fuzzy-search:rebuild "App\Models\Contact"

npm install && npm run build
php artisan serve
```

Open <http://127.0.0.1:8000> in your browser.

The demo runs no queue worker, so `AppServiceProvider` forces `fuzzy-search.indexing.async` to `false` — index updates (including `reindexRelated()` on a category/author rename) happen synchronously on the request instead of sitting unprocessed in the `jobs` table.

## Test Data

The default seeder (`php artisan db:seed`) creates:

| Model | Count | Searchable columns |
| ----- | ----- | ------------------ |
| Users | 24 | `name`, `email` |
| Products | 50 | `name`, `description`, `sku`, `brand`, `category.name` |
| Articles | 30 | `title`, `body`, `excerpt`, `author.name` |
| Contacts | 40 | `first_name`, `last_name`, `email`, `company` |

### Large dataset (benchmarks)

To reproduce benchmark numbers with a realistic dataset:

```bash
# 400k rows (100k per model) — takes ~2 minutes
php artisan db:seed --class=Database\\Seeders\\LargeDatasetSeeder

# 1 million users — takes several minutes; disabled in APP_ENV=production
php artisan demo:seed --huge
```

## Test Scenarios

### 1. Typo tolerance (`/search/users`)

- Search `jonh` → finds "John" (single-character transposition)
- Search `smyth` → finds "Smith" (phonetic near-match)
- Slide the typo tolerance control to see how threshold affects recall

### 2. Algorithm comparison (`/search/users`)

Switch between all eight algorithms on the same query and compare ranked output:

| Algorithm | Strengths |
| --------- | --------- |
| `fuzzy` | General purpose, handles typos |
| `levenshtein` | Edit-distance with configurable threshold |
| `soundex` | English phonetic matching |
| `metaphone` | More accurate phonetics than Soundex |
| `trigram` | N-gram similarity (best on PostgreSQL with `pg_trgm`) |
| `similar_text` | Percentage-based similarity |
| `simple` | Basic LIKE — fastest, no typo tolerance |
| `like` | Wildcard LIKE |

### 3. BM25 inverted index (`/search/benchmarks`)

The benchmarks page runs the same term through LIKE-pattern search and the BM25 inverted index and prints both latencies, keeping the last five runs in `localStorage`. Set **Typo tolerance on the index** above 0 to add a third panel: the same index read with each term widened by edit distance. Try `jonh` at 0 (few or no index rows — the dictionary has no such term) and at 2 (the John rows come back, and the extra milliseconds are the cost of dictionary expansion).

### 4. Extended syntax playground (`/search/playground`)

Try these queries:

| Query | Meaning |
| ----- | ------- |
| `'john` | Soft contains — "john" appears anywhere |
| `=John Doe` | Exact phrase match |
| `^john` | Starts with "john" |
| `doe$` | Ends with "doe" |
| `!smith` | Exclude records matching "smith" |
| `john \| jane` | OR — matches either |
| `john !(doe \| smith)` | john, but not doe or smith |
| `~jonh` | Typo-tolerant term — edit-distance match inside an extended query |
| `email:^jon` | Field scope — apply the operator to one column only |

### 5. Phonetic search (`/search/contacts`)

- Search `steven` → finds "Stephen", "Stefan"
- Search `katherine` → finds "Catherine", "Kathryn"
- Search `jon` → finds "John", "Jonathan"

### 6. Config presets (`/search/products`)

Compare `ecommerce` preset (typo-tolerant, partial match) against `exact` preset (strict, no typos) on the same query.

The same page shows weighted BM25. Every fixed catalog product has a real description that names other products, so `macbook` on the BM25 engine ranks **MacBook Pro 16"** (title, weight 10) above **Dell XPS 15** and **iPad Pro 12.9"**, which only mention it in the description (weight 5). The as-you-type box above the form hits `/api/as-you-type` after two characters and completes from the dictionary — type `macb`, `ipho` or `nespr`.

### 7. Scout driver (`/search/scout-demo`)

Demonstrates `SCOUT_DRIVER=fuzzy-search` — the bundled Scout engine adapter. No extra package required.

### 8. Federated search (`/search/federated`)

Searches Users, Products, Articles, and Contacts in a single query and merges results with unified `_score` ranking.

### 9. Relationship search (`/search/relations`)

Search `smith` — the author "John Smith" is in the fixed seeded pool, so the match is scored and highlighted under the dotted `author.name` key, not on the article's own columns. On the BM25 index (the default) the author's name is indexed with each article; switch the engine to the LIKE table scan and `searchIn(['author.name' => 6])` compiles to a `whereHas`/EXISTS clause instead.

### 10. Persisted search analytics (`/search/analytics`)

Run a handful of searches on the other pages first (`analytics.enabled` is on in `AppServiceProvider`, the queue is disabled, so rows land in `fuzzy_search_logs` synchronously). The page then reads the `SearchAnalytics` facade for popular terms, zero-result terms, latency by path and daily volume.

### 11. CJK search (`/search/i18n`)

The app registers `ScriptAwareTokenizer` globally, so Han/Hiragana/Katakana/Hangul runs are indexed as character bigrams and everything else stays whitespace-tokenized. Search `東京` (finds the Tokyo Tower model and the Kyoto matcha set, whose description mentions 東京), `烤鸭`, `ヘッドホン`, `김치`, and `tokyo` — the ASCII gloss in the description still matches on the same index.

### 12. As-you-type on the index (`/search/products`)

The box above the product search form calls `/api/as-you-type`, which runs `Product::search($q)->useInvertedIndex()->asYouType()->typoTolerance(1)->highlight('mark')->limit(8)` and returns `FuzzySearchCollection`: `data[]` rows with `_score` and escaped `_highlighted` values, plus `meta.algorithm` and `meta.latency_ms` shown under the results.

## Requirements

- PHP 8.2+
- Laravel 12.x
- `ashiqfardus/laravel-fuzzy-search` ^2.1
- MySQL 8+, MariaDB 11.4, PostgreSQL 14+, SQL Server 2022, SQLite (the package's CI matrix)

## Project Structure

```text
app/
├── Console/Commands/
│   └── SeedCommand.php            # php artisan demo:seed [--huge]
├── Http/Controllers/
│   └── SearchController.php       # All search logic
├── Models/
│   ├── User.php                   # Searchable + BM25 index
│   ├── Product.php                # belongsTo Category; searchableText()/searchIndexQuery()
│   ├── Article.php                # belongsTo Author; searchableText()/searchIndexQuery()
│   ├── Category.php               # reindexRelated() on rename
│   ├── Author.php                 # reindexRelated() on rename
│   └── Contact.php
database/
├── migrations/
└── seeders/
    ├── DatabaseSeeder.php         # Default: 24 users, 50 products, 30 articles, 40 contacts
    ├── LargeDatasetSeeder.php     # 100k rows per model (400k total)
    └── HugeDatasetSeeder.php      # 1M users
resources/views/
├── layouts/app.blade.php
├── partials/tour-overlay.blade.php
└── search/
    ├── index.blade.php
    ├── users.blade.php
    ├── products.blade.php
    ├── relations.blade.php
    ├── articles.blade.php
    ├── contacts.blade.php
    ├── federated.blade.php
    ├── smart.blade.php
    ├── benchmarks.blade.php
    ├── capability-matrix.blade.php
    ├── scout-demo.blade.php
    └── playground.blade.php
routes/
└── web.php                        # HTML routes: throttle:30,1 | API routes: throttle:60,1
```

## License

MIT — see [LICENSE](LICENSE).
