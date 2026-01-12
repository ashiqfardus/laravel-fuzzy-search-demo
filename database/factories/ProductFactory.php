<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $products = [
            ['name' => 'iPhone 15 Pro Max', 'brand' => 'Apple', 'category' => 'Smartphones'],
            ['name' => 'Samsung Galaxy S24 Ultra', 'brand' => 'Samsung', 'category' => 'Smartphones'],
            ['name' => 'MacBook Pro 16"', 'brand' => 'Apple', 'category' => 'Laptops'],
            ['name' => 'Dell XPS 15', 'brand' => 'Dell', 'category' => 'Laptops'],
            ['name' => 'Sony WH-1000XM5', 'brand' => 'Sony', 'category' => 'Headphones'],
            ['name' => 'AirPods Pro 2', 'brand' => 'Apple', 'category' => 'Headphones'],
            ['name' => 'iPad Pro 12.9"', 'brand' => 'Apple', 'category' => 'Tablets'],
            ['name' => 'Samsung Galaxy Tab S9', 'brand' => 'Samsung', 'category' => 'Tablets'],
            ['name' => 'PlayStation 5', 'brand' => 'Sony', 'category' => 'Gaming'],
            ['name' => 'Xbox Series X', 'brand' => 'Microsoft', 'category' => 'Gaming'],
            ['name' => 'Nintendo Switch OLED', 'brand' => 'Nintendo', 'category' => 'Gaming'],
            ['name' => 'LG OLED C3 65"', 'brand' => 'LG', 'category' => 'TVs'],
            ['name' => 'Samsung Neo QLED 8K', 'brand' => 'Samsung', 'category' => 'TVs'],
            ['name' => 'Dyson V15 Detect', 'brand' => 'Dyson', 'category' => 'Home Appliances'],
            ['name' => 'Nespresso Vertuo Plus', 'brand' => 'Nespresso', 'category' => 'Kitchen'],
        ];

        $product = fake()->randomElement($products);

        return [
            'name' => $product['name'],
            'description' => fake()->paragraph(3),
            'sku' => strtoupper(fake()->unique()->bothify('???-#####')),
            'brand' => $product['brand'],
            'category' => $product['category'],
            'price' => fake()->randomFloat(2, 29.99, 2999.99),
        ];
    }
}
