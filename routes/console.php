<?php

use Illuminate\Support\Facades\Schedule;

// Search terms are visitor input: keep the log to analytics.retention_days (30). Needs the
// scheduler (php artisan schedule:run every minute, or schedule:work).
Schedule::command('fuzzy-search:analytics:prune')->daily();
