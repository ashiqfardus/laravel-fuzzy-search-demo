<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    protected function tearDown(): void
    {
        TrustProxies::flushState();
        parent::tearDown();
    }

    public function test_trusted_proxies_from_config_give_each_visitor_their_own_ip(): void
    {
        config(['app.trusted_proxies' => '10.0.0.1, 10.0.0.2']);
        (new AppServiceProvider($this->app))->boot();
        Route::get('/_ip', fn () => request()->ip());

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->get('/_ip', ['X-Forwarded-For' => '203.0.113.7'])
            ->assertSee('203.0.113.7');
    }

    public function test_without_trusted_proxies_the_forwarded_header_is_ignored(): void
    {
        config(['app.trusted_proxies' => null]);
        (new AppServiceProvider($this->app))->boot();
        Route::get('/_ip', fn () => request()->ip());

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->get('/_ip', ['X-Forwarded-For' => '203.0.113.7'])
            ->assertSee('10.0.0.2')
            ->assertDontSee('203.0.113.7');
    }
}
