export function fuzzyTour() {
    const steps = [
        {
            title:  '1 / 8 — Multi-Algorithm User Search',
            body:   'The BM25 index answers by default, typo-tolerant. Switch the engine to the LIKE table scan to compare fuzzy, levenshtein, soundex, metaphone and simple LIKE, and chain a fallback.',
            href:   '/search/users',
        },
        {
            title:  '2 / 8 — Product Search (Config Presets)',
            body:   'Weighted BM25 by default: try macbook. On the LIKE table scan, the ecommerce and exact presets from config/fuzzy-search.php set the algorithm and typo tolerance.',
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
            body:   'SCOUT_DRIVER=fuzzy-search wires in the package as a Laravel Scout engine once laravel/scout is installed. No separate driver package.',
            href:   '/search/scout-demo',
        },
        {
            title:  '8 / 8 — Capability Matrix',
            body:   'The same term through seven LIKE algorithms side by side, with their timings, over the first 10,000 users.',
            href:   '/search/capability-matrix',
        },
    ];

    const STORAGE_KEY = 'fzs_tour';

    // On the start card (the dashboard) the tour is a modal; on a stop's own page the card is
    // docked beside it (no backdrop) so the page stays usable, and it describes that page.
    return {
        active:  false,
        docked:  false,
        step:    0,
        steps,
        get current()  { return this.steps[this.step]; },
        get isLast()   { return this.step === this.steps.length - 1; },
        get here()     { return this.current.href === window.location.pathname; },
        start()  {
            this.step = 0;
            this.active = true;
            this.docked = false;
            this._save();
        },
        // Open the current stop's page. The href is read before anything changes, so the page
        // opened is always the one the card names.
        go()     {
            this._save();
            window.location.assign(this.current.href);
        },
        next()   {
            if (this.isLast) { this.finish(); return; }
            this.step++;
            this.go();
        },
        finish() {
            this.active = false;
            sessionStorage.removeItem(STORAGE_KEY);
        },
        restore() {
            try {
                const saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) ?? 'null');
                if (saved && saved.active && Number.isInteger(saved.step)
                    && saved.step >= 0 && saved.step < this.steps.length) {
                    // Back/forward or the nav can land on another stop: show that stop's card.
                    const here = this.steps.findIndex(s => s.href === window.location.pathname);
                    this.step   = here >= 0 ? here : saved.step;
                    this.active = true;
                    this.docked = true;
                    this._save();
                }
            } catch {}
        },
        _save() {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ active: this.active, step: this.step }));
        },
    };
}
