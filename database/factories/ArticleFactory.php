<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $titles = [
            'Getting Started with Laravel Fuzzy Search',
            'How to Implement Typo-Tolerant Search in PHP',
            'Building a Modern Search Experience with Laravel',
            'Fuzzy Matching Algorithms Explained',
            'Levenshtein Distance: A Deep Dive',
            'Soundex and Phonetic Search in Practice',
            'Optimizing Database Search Performance',
            'Full-Text Search vs Fuzzy Search',
            'Creating Autocomplete Features in Laravel',
            'Search Relevance Scoring Best Practices',
            'Multilingual Search Implementation Guide',
            'Stop Words and Search Optimization',
            'Building E-commerce Search with Laravel',
            'Real-time Search with Debouncing',
            'PostgreSQL Trigram Search Tutorial',
        ];

        // Real English, not Faker lorem: the index expands typos, and short lorem words made
        // the relations page's own example ("smith") match most of the articles.
        $sentences = [
            'A search box that forgives a misspelled word keeps visitors on the page.',
            'Rank the closest spellings first and let the rest follow.',
            'Test every query against the real data before it ships.',
            'An index answers in milliseconds where a table scan takes seconds.',
            'Weight the title above the body, because readers scan titles first.',
            'Measure latency on the largest table you expect to run.',
            'Phonetic matching finds names that sound alike but are spelled differently.',
            'Stop words add noise, so the engine ignores them when it scores a query.',
            'Highlighting the matched part of a result tells the reader why it was found.',
            'Cache the popular queries and rebuild the index when the data changes.',
        ];

        return [
            'title' => fake()->randomElement($titles) . ' - Part ' . fake()->numberBetween(1, 5),
            'body' => implode("\n\n", array_map(fn () => implode(' ', fake()->randomElements($sentences, 3)), range(1, 3))),
            'excerpt' => implode(' ', fake()->randomElements($sentences, 2)),
            'author_id' => Author::factory(),
            'status' => fake()->randomElement(['draft', 'published', 'published', 'published', 'archived']),
        ];
    }
}
