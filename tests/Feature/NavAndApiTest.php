<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavAndApiTest extends TestCase
{
    use RefreshDatabase;

    /** Every page reaches analytics and CJK search from the header (N5-L10). */
    public function test_the_header_links_analytics_and_cjk_search(): void
    {
        $this->get('/search/users')->assertOk()
            ->assertSee(route('search.analytics'), false)
            ->assertSee(route('search.i18n'), false);
    }

    /** The users API does not serialise the shadow columns (N5-L11). */
    public function test_the_users_api_hides_the_metaphone_columns(): void
    {
        User::factory()->create(['name' => 'Jon Snow', 'email' => 'jon.snow@example.com']);

        $this->assertArrayNotHasKey('name_metaphone', User::first()->toArray());
        $this->assertArrayNotHasKey('email_metaphone', User::first()->toArray());
        $this->getJson('/api/search/users?q=jon')->assertOk()
            ->assertJsonPath('data.0.email', 'jon.snow@example.com')
            ->assertJsonMissingPath('data.0.name_metaphone')
            ->assertJsonMissingPath('data.0.email_metaphone')
            ->assertJsonMissingPath('data.0.password');
    }
}
