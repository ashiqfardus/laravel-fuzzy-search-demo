export function fuzzyTour() {
    const steps = [
        {
            title:  '1 / 8 — Multi-Algorithm User Search',
            body:   'Try levenshtein, soundex, fuzzy, and metaphone side-by-side. Toggle typo tolerance and see how ranking changes.',
            href:   '/search/users',
        },
        {
            title:  '2 / 8 — Product Search (Config Presets)',
            body:   'Pick a preset (ecommerce, blog, exact) — each tunes eight parameters at once via config/fuzzy-search.php.',
            href:   '/search/products',
        },
        {
            title:  '3 / 8 — Extended Syntax Playground',
            body:   "Type =exact, ^prefix, word$, !excluded, or 'include. Chain with | for OR. Powered by the built-in Lexer → AST → SQL compiler.",
            href:   '/search/playground',
        },
        {
            title:  '4 / 8 — Article Search (Token Matching)',
            body:   'Test matchAll vs matchAny tokenization on multi-word queries. Good for blog-style content.',
            href:   '/search/articles',
        },
        {
            title:  '5 / 8 — Federated Search',
            body:   'Search across Users, Products, Articles, and Contacts simultaneously. Results are merged and ranked by a normalized _score.',
            href:   '/search/federated',
        },
        {
            title:  '6 / 8 — BM25 Benchmark',
            body:   'BM25 inverted index vs LIKE — same query, same dataset. See the latency and ranking difference side-by-side.',
            href:   '/search/benchmarks',
        },
        {
            title:  '7 / 8 — Scout Driver',
            body:   'SCOUT_DRIVER=fuzzy-search wires in the package as a Laravel Scout engine. No extra package required.',
            href:   '/search/scout-demo',
        },
        {
            title:  '8 / 8 — Capability Matrix',
            body:   'Every feature, every DB engine, every algorithm. Green means it works on your hardware right now.',
            href:   '/search/capability-matrix',
        },
    ];

    return {
        active:  false,
        step:    0,
        steps,
        get current()  { return this.steps[this.step]; },
        get isLast()   { return this.step === this.steps.length - 1; },
        start()  { this.step = 0; this.active = true; },
        next()   { this.isLast ? this.finish() : this.step++; },
        prev()   { if (this.step > 0) this.step--; },
        finish() { this.active = false; },
    };
}
