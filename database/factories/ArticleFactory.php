<?php

namespace Database\Factories;

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

        return [
            'title' => fake()->randomElement($titles) . ' - Part ' . fake()->numberBetween(1, 5),
            'body' => fake()->paragraphs(5, true),
            'excerpt' => fake()->paragraph(),
            'author' => fake()->name(),
            'status' => fake()->randomElement(['draft', 'published', 'published', 'published', 'archived']),
        ];
    }
}
