<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * The fixed catalog of realistic product names, each with its category's name. Exposed as a
     * public static method (not just a local to
     * definition()) so DatabaseSeeder can firstOrCreate() every entry before drawing random
     * duplicates — otherwise a random draw of 50 from 15 names can silently skip one, and
     * whichever page hint mentions that product stops matching until the next reseed.
     * Every entry carries a real description that mentions other products by name, so
     * weighted BM25 (name 10 vs description 5) has something to rank on `/search/products`.
     */
    public static function catalog(): array
    {
        // No category ids here: an id kept for the process outlives a refreshed database (each
        // test's), so the caller resolves the category it uses (category()).
        return [
                ['name' => 'iPhone 15 Pro Max', 'brand' => 'Apple', 'category' => 'Smartphones', 'description' => 'Apple titanium flagship with a 6.7-inch display, the A17 Pro chip and USB-C. It pairs instantly with AirPods Pro 2 and mirrors to an iPad Pro for sketching. Buyers cross-shopping the Samsung Galaxy S24 Ultra usually decide on camera processing.'],
                ['name' => 'Samsung Galaxy S24 Ultra', 'brand' => 'Samsung', 'category' => 'Smartphones', 'description' => 'A 6.8-inch flagship with a built-in S Pen, a 200 MP camera and seven years of updates. It shares DeX desktop mode with the Galaxy Tab S9 and casts straight to a Neo QLED 8K panel. The usual comparison is the iPhone 15 Pro Max on zoom range.'],
                ['name' => 'MacBook Pro 16"', 'brand' => 'Apple', 'category' => 'Laptops', 'description' => 'A 16-inch laptop with the M3 Pro chip, 22-hour battery and a mini-LED display. Developers pick it over the Dell XPS 15 for quiet thermals under sustained load. Sidecar turns an iPad Pro into a second display.'],
                ['name' => 'Dell XPS 15', 'brand' => 'Dell', 'category' => 'Laptops', 'description' => 'A 15.6-inch Windows laptop with an OLED screen option, discrete graphics and a machined aluminium chassis. It is the usual MacBook Pro alternative for teams on Windows-only tooling. Thunderbolt 4 drives two external panels, an LG OLED C3 included.'],
                ['name' => 'Sony WH-1000XM5', 'brand' => 'Sony', 'category' => 'Headphones', 'description' => 'Over-ear wireless headphones with eight microphones for noise cancelling and 30 hours of battery. Multipoint keeps a MacBook Pro and an iPhone connected at the same time. Reviewers rank them against AirPods Pro 2 for commuting.'],
                ['name' => 'AirPods Pro 2', 'brand' => 'Apple', 'category' => 'Headphones', 'description' => 'In-ear buds with adaptive transparency, conversation awareness and a USB-C case. They switch automatically between an iPhone 15 Pro Max and an iPad Pro. The Sony WH-1000XM5 is the over-ear alternative for long flights.'],
                ['name' => 'iPad Pro 12.9"', 'brand' => 'Apple', 'category' => 'Tablets', 'description' => 'A 12.9-inch tablet with the M2 chip, a mini-LED display and Apple Pencil hover. It doubles as a MacBook Pro second screen over Sidecar. Against the Samsung Galaxy Tab S9 the difference is desktop-class apps.'],
                ['name' => 'Samsung Galaxy Tab S9', 'brand' => 'Samsung', 'category' => 'Tablets', 'description' => 'An 11-inch AMOLED tablet with an S Pen in the box and an IP68 rating. DeX mode brings the Galaxy S24 Ultra desktop experience to a larger screen. It is the Android answer to the iPad Pro.'],
                ['name' => 'PlayStation 5', 'brand' => 'Sony', 'category' => 'Gaming', 'description' => 'A Sony console with an SSD-backed loading pipeline, 4K120 output and the DualSense haptic controller. It looks its best on an LG OLED C3 with variable refresh rate enabled. The Xbox Series X is the usual cross-shop for subscription-first households.'],
                ['name' => 'Xbox Series X', 'brand' => 'Microsoft', 'category' => 'Gaming', 'description' => 'A 12-teraflop console with Quick Resume and Dolby Vision gaming. Game Pass replaces most disc buying, and the PlayStation 5 is the alternative for exclusives. A Neo QLED 8K panel handles the 4K120 signal without scaling.'],
                ['name' => 'Nintendo Switch OLED', 'brand' => 'Nintendo', 'category' => 'Gaming', 'description' => 'A hybrid console with a 7-inch OLED screen, a wider stand and 64 GB of storage. Docked play on an LG OLED C3 is 1080p, handheld is 720p. It sits alongside a PlayStation 5 rather than replacing it.'],
                ['name' => 'LG OLED C3 65"', 'brand' => 'LG', 'category' => 'TVs', 'description' => 'A 65-inch OLED television with four HDMI 2.1 ports, 120 Hz variable refresh rate and Dolby Vision. It is the panel of choice for a PlayStation 5 or an Xbox Series X. Against the Samsung Neo QLED 8K it trades peak brightness for perfect blacks.'],
                ['name' => 'Samsung Neo QLED 8K', 'brand' => 'Samsung', 'category' => 'TVs', 'description' => 'An 8K mini-LED television with quantum matrix backlight control and very high peak brightness. Bright rooms favour it over the LG OLED C3. It upscales 4K120 from an Xbox Series X and mirrors a Galaxy S24 Ultra over Tap View.'],
                ['name' => 'Dyson V15 Detect', 'brand' => 'Dyson', 'category' => 'Home Appliances', 'description' => 'A cordless vacuum with a laser dust head, a piezo particle counter and 60 minutes of run time. The sealed HEPA stack keeps fine dust in the bin instead of the room. Households that already run a Nespresso Vertuo Plus buy the hard-floor head for spilled grounds.'],
                ['name' => 'Nespresso Vertuo Plus', 'brand' => 'Nespresso', 'category' => 'Kitchen', 'description' => 'A capsule machine with centrifusion extraction, five cup sizes and a 1.2-litre tank. It brews espresso through to alto without changing baskets. Ground spills are easy work for a Dyson V15 Detect.'],
                // CJK catalog — script-aware tokenizer (character bigrams for Han/Hiragana/
                // Katakana/Hangul runs, whitespace for the rest). /search/i18n's description
                // fields carry the ASCII "Tokyo"/"Kyoto" gloss that the plain-Latin query needs.
                ['name' => '東京タワー 模型', 'brand' => 'Bandai', 'category' => 'Souvenirs', 'description' => "A die-cast miniature of Tokyo Tower (東京タワー), Japan's iconic red steel landmark."],
                ['name' => '北京烤鸭 调味料', 'brand' => 'Quanjude', 'category' => 'Kitchen', 'description' => 'A ready-to-use marinade and glaze for authentic Beijing-style roast duck at home.'],
                ['name' => 'ソニー ヘッドホン', 'brand' => 'Sony', 'category' => 'Headphones', 'description' => "Over-ear wireless headphones with active noise cancelling, in Sony's Japan-market packaging."],
                ['name' => '华为 手机壳', 'brand' => 'Huawei', 'category' => 'Accessories', 'description' => 'A slim protective case for Huawei smartphones with reinforced corners.'],
                ['name' => '京都 抹茶 セット', 'brand' => 'Ippodo', 'category' => 'Souvenirs', 'description' => "A ceremonial-grade matcha gift set from Kyoto, once Japan's imperial capital before Tokyo (東京) became the seat of government."],
                ['name' => '서울 김치 세트', 'brand' => 'Jongga', 'category' => 'Kitchen', 'description' => 'A jarred set of napa cabbage and radish kimchi, fermented in small batches in Seoul.'],
        ];
    }

    /** The id of a catalog entry's category, created on first use. */
    public static function category(array $entry): int
    {
        return Category::firstOrCreate(['name' => $entry['category']])->id;
    }

    public function definition(): array
    {
        $product = fake()->randomElement(self::catalog());

        return [
            'name' => $product['name'],
            'description' => $product['description'],
            'sku' => fake()->numerify('SKU-##########'),
            'brand' => $product['brand'],
            'category_id' => self::category($product),
            'price' => fake()->randomFloat(2, 29.99, 2999.99),
        ];
    }
}
