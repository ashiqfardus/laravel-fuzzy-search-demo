<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** WF-11: a list page shows the real match count and a pager, not the builder's silent top 15. */
class ListPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_shows_every_match_across_pages(): void
    {
        User::factory(25)->sequence(fn ($s) => ['name' => 'Zorbak Unit' . $s->index])->create();

        $this->get('/search/users?q=zorbak')->assertOk()
            ->assertSee('25 results')->assertSee('Page 1 of 3');
        $this->get('/search/users?q=zorbak&page=3')->assertOk()->assertSee('Page 3 of 3');
    }

    public function test_products_shows_every_match_across_pages(): void
    {
        $category = Category::create(['name' => 'Gadgets']);
        Product::factory(25)->sequence(fn ($s) => ['name' => 'Quuxbar Widget' . $s->index, 'category_id' => $category->id])->create();

        $this->get('/search/products?q=quuxbar')->assertOk()
            ->assertSee('25 results')->assertSee('Page 1 of 3');
    }

    public function test_contacts_shows_every_match_across_pages(): void
    {
        Contact::factory(25)->sequence(fn ($s) => ['first_name' => 'Zorbak', 'last_name' => 'Unit' . $s->index])->create();

        $this->get('/search/contacts?q=zorbak&algorithm=fuzzy')->assertOk()
            ->assertSee('25 results')->assertSee('Page 1 of 3');
    }
}
