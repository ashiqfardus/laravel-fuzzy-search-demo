<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/** The package refuses hidden and secret-named columns; no demo route can reach one. */
class HiddenColumnGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_package_refuses_to_search_a_hidden_column(): void
    {
        $this->expectException(InvalidArgumentException::class);

        User::search('x')->searchIn(['password'])->get();
    }

    public function test_the_package_refuses_to_filter_on_a_hidden_column(): void
    {
        $this->expectException(InvalidArgumentException::class);

        User::search('x')->filter('remember_token', 'x')->get();
    }

    public function test_no_page_serves_a_hidden_column_when_a_query_names_one(): void
    {
        User::factory()->create(['name' => 'Guard Person', 'password' => 'secret-hash-value']);

        foreach (['password', 'remember_token', 'name_metaphone', 'email_metaphone'] as $column) {
            $response = $this->get('/search/playground?q=' . urlencode($column . ':secret'));
            $this->assertContains($response->getStatusCode(), [200, 422], $column);
            $this->assertStringNotContainsString('secret-hash-value', $response->getContent(), $column);

            foreach (['/search/users', '/search/federated', '/search/smart'] as $page) {
                $this->get($page . '?searchIn[]=' . $column . '&q=guard')->assertOk()->assertDontSee('secret-hash-value');
            }
        }
        $this->getJson('/api/search/users?q=guard')->assertOk()->assertJsonMissingPath('data.0.password');
    }
}
