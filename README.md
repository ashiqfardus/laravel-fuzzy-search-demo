# Laravel Fuzzy Search Demo

A demonstration application showcasing the [ashiqfardus/laravel-fuzzy-search](https://github.com/ashiqfardus/laravel-fuzzy-search) package — **v2.0.0**.

## Links

- **Package:** [ashiqfardus/laravel-fuzzy-search](https://github.com/ashiqfardus/laravel-fuzzy-search)
- **Packagist:** [packagist.org/packages/ashiqfardus/laravel-fuzzy-search](https://packagist.org/packages/ashiqfardus/laravel-fuzzy-search)

## Features Demonstrated

| Page | What it shows |
| ---- | ------------- |
| `/search/users` | 8 algorithms, typo tolerance, relevance scoring, `@fuzzyHighlight` |
| `/search/products` | Config presets (`ecommerce`, `exact`), field weighting |
| `/search/articles` | Multi-word tokenization with AND / OR logic |
| `/search/contacts` | Phonetic search — Soundex & Metaphone shadow columns |
| `/search/federated` | Cross-model federated search with unified ranking |
| `/search/smart` | `didYouMean()`, autocomplete suggestions, in-memory search |
| `/search/benchmarks` | Side-by-side timing: LIKE vs BM25 inverted index across dataset sizes |
| `/search/capability-matrix` | Feature availability per algorithm and database driver |
| `/search/scout-demo` | Laravel Scout integration (`SCOUT_DRIVER=fuzzy-search`) |
| `/search/playground` | Extended query syntax: include `'`, exact `=`, prefix `^`, suffix `$`, exclude `!`, OR, grouping `()` |

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

## Test Data

The default seeder (`php artisan db:seed`) creates:

| Model | Count | Searchable columns |
| ----- | ----- | ------------------ |
| Users | 24 | `name`, `email` |
| Products | 50 | `name`, `description`, `sku`, `brand`, `category` |
| Articles | 30 | `title`, `body`, `excerpt`, `author` |
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

After running a large seeder, the benchmarks page compares query time for LIKE-pattern search vs `useInvertedIndex()` at 10k, 100k, and 1M rows. BM25 is faster than LIKE on tables above ~500k rows.

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

### 5. Phonetic search (`/search/contacts`)

- Search `steven` → finds "Stephen", "Stefan"
- Search `katherine` → finds "Catherine", "Kathryn"
- Search `jon` → finds "John", "Jonathan"

### 6. Config presets (`/search/products`)

Compare `ecommerce` preset (typo-tolerant, partial match) against `exact` preset (strict, no typos) on the same query.

### 7. Scout driver (`/search/scout-demo`)

Demonstrates `SCOUT_DRIVER=fuzzy-search` — the bundled Scout engine adapter. No extra package required.

### 8. Federated search (`/search/federated`)

Searches Users, Products, Articles, and Contacts in a single query and merges results with unified `_score` ranking.

## Requirements

- PHP 8.2+
- Laravel 12.x
- `ashiqfardus/laravel-fuzzy-search` ^2.0
- SQLite (default), MySQL 5.7+, PostgreSQL 12+, MariaDB, or SQL Server

## Project Structure

```text
app/
├── Console/Commands/
│   └── SeedCommand.php            # php artisan demo:seed [--huge]
├── Http/Controllers/
│   └── SearchController.php       # All search logic
├── Models/
│   ├── User.php                   # Searchable + BM25 index
│   ├── Product.php
│   ├── Article.php
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
