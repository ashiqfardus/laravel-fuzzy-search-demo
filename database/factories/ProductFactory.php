<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * The fixed catalog of realistic product names, each mapped to a resolved category id.
     * Built once per process. Exposed as a public static method (not just a local to
     * definition()) so DatabaseSeeder can firstOrCreate() every entry before drawing random
     * duplicates — otherwise a random draw of 50 from 15 names can silently skip one, and
     * whichever page hint mentions that product stops matching until the next reseed.
     */
    public static function catalog(): array
    {
        static $products = null;

        // Built once per process: the category row only needs firstOrCreate() the first time
        // a given name is seen, not once per generated product.
        return $products ??= array_map(
            fn (array $p) => $p + ['category_id' => Category::firstOrCreate(['name' => $p['category']])->id],
            [
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
            ]
        );
    }

    public function definition(): array
    {
        $product = fake()->randomElement(self::catalog());

        return [
            'name' => $product['name'],
            'description' => fake()->paragraph(3),
            'sku' => strtoupper(fake()->unique()->bothify('???-#####')),
            'brand' => $product['brand'],
            'category_id' => $product['category_id'],
            'price' => fake()->randomFloat(2, 29.99, 2999.99),
        ];
    }
}
