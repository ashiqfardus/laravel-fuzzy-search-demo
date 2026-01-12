<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    public function definition(): array
    {
        // Mix of similar sounding names for phonetic search testing
        $firstNames = [
            'John', 'Jon', 'Johnny', 'Jonathan',
            'Steven', 'Stephen', 'Stefan',
            'Michael', 'Michel', 'Miguel',
            'Catherine', 'Katherine', 'Kathryn',
            'Jeffrey', 'Geoffrey', 'Jeffery',
            'Philip', 'Phillip', 'Filip',
            'Brian', 'Bryan', 'Brayan',
            'Eric', 'Erik', 'Erich',
            'Jason', 'Jayson', 'Jacen',
            'Sarah', 'Sara', 'Zahra',
        ];

        return [
            'first_name' => fake()->randomElement($firstNames),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
        ];
    }
}
