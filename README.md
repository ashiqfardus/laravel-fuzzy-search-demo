# Laravel Fuzzy Search Demo

A demonstration application showcasing the [Laravel Fuzzy Search](https://github.com/ashiqfardus/laravel-fuzzy-search) package features.

## 🔗 Links

- **Package Repository:** [ashiqfardus/laravel-fuzzy-search](https://github.com/ashiqfardus/laravel-fuzzy-search)
- **Packagist:** [ashiqfardus/laravel-fuzzy-search](https://packagist.org/packages/ashiqfardus/laravel-fuzzy-search)

## ✨ Features Demonstrated

| Feature | Description | Page |
|---------|-------------|------|
| **Algorithm Selection** | Test 8 different search algorithms | `/search/users` |
| **Typo Tolerance** | Configurable typo tolerance (0-5) | `/search/users` |
| **Config Presets** | Pre-configured search settings | `/search/products` |
| **Phonetic Search** | Soundex & Metaphone matching | `/search/contacts` |
| **Tokenization** | Multi-word search with AND/OR | `/search/articles` |
| **Federated Search** | Search across multiple models | `/search/federated` |
| **Smart Search** | Did You Mean, Suggestions, Autocomplete | `/search/smart` |
| **Relevance Scoring** | Results ranked by relevance | All pages |
| **Result Highlighting** | Search terms highlighted | All pages |

## 🚀 Installation

```bash
# Clone the repository
git clone https://github.com/ashiqfardus/laravel-fuzzy-search-demo.git

# Navigate to the project
cd laravel-fuzzy-search-demo

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations and seed data
php artisan migrate --seed

# Start the development server
php artisan serve
```

Then open **http://127.0.0.1:8000** in your browser.

## 📊 Test Data

The seeder creates sample data for testing:

| Model | Count | Searchable Columns |
|-------|-------|-------------------|
| Users | 24 | name, email |
| Products | 50 | name, description, sku, brand, category |
| Articles | 30 | title, body, excerpt, author |
| Contacts | 40 | first_name, last_name, email, company |

## 🧪 Test Scenarios

### 1. Typo Tolerance (User Search)
Navigate to `/search/users` and try:
- Search `jonh` → Finds "John" (typo correction)
- Search `jon` → Finds "John", "Jon", "Jonathan"
- Change typo tolerance to see different results

### 2. Algorithm Comparison
On the user search page, compare algorithms:
- **Fuzzy** - General purpose, fast
- **Levenshtein** - Edit distance based
- **Soundex** - Phonetic (English)
- **Metaphone** - Phonetic (more accurate)
- **Simple** - Basic LIKE query

### 3. Phonetic Search (Contacts)
Navigate to `/search/contacts` and try:
- Search `steven` → Finds "Stephen", "Stefan"
- Search `jon` → Finds "John", "Jonathan"
- Search `katherine` → Finds "Catherine", "Kathryn"

### 4. Config Presets (Products)
Navigate to `/search/products` and compare:
- **E-commerce preset** - Typo tolerant, partial match
- **Exact preset** - Strict matching, no typos

### 5. Tokenized Search (Articles)
Navigate to `/search/articles` and try:
- Search `laravel search` with **Match Any** (OR logic)
- Search `laravel search` with **Match All** (AND logic)

### 6. Federated Search
Navigate to `/search/federated` to search across all models simultaneously.

### 7. Smart Search Features
Navigate to `/search/smart` and try:
- Search `volet` → See "Violet VonRueden" with similarity score (412.01)
- Search `iphne` → See "iPhone" products with proper scoring
- View autocomplete suggestions as you type
- Click on suggestions to refine your search

## 📁 Project Structure

```
app/
├── Http/Controllers/
│   └── SearchController.php    # All search logic
├── Models/
│   ├── User.php               # With Searchable trait
│   ├── Product.php            # With Searchable trait
│   ├── Article.php            # With Searchable trait
│   └── Contact.php            # With Searchable trait
database/
├── factories/                  # Test data factories
├── migrations/                 # Database schema
└── seeders/
    └── DatabaseSeeder.php     # Seeds all test data
resources/views/
├── layouts/app.blade.php      # Main layout
└── search/
    ├── index.blade.php        # Dashboard
    ├── users.blade.php        # User search
    ├── products.blade.php     # Product search
    ├── articles.blade.php     # Article search
    ├── contacts.blade.php     # Contact search
    └── federated.blade.php    # Multi-model search
routes/
└── web.php                    # All routes
```

## 🛠️ Requirements

- PHP 8.2+
- Laravel 12.x or 13.x-dev
- SQLite (default) or MySQL/PostgreSQL

## 📝 License

MIT License - See [LICENSE](LICENSE) for details.

## 🔗 Related

- [Laravel Fuzzy Search Package](https://github.com/ashiqfardus/laravel-fuzzy-search)
- [Package Documentation](https://github.com/ashiqfardus/laravel-fuzzy-search#readme)

