<?php

declare(strict_types=1);

namespace Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class NoIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_noindex_meta_tag_is_rendered_when_enabled(): void
    {
        View::share('noIndex', true);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex">', false);
    }

    public function test_noindex_meta_tag_is_not_rendered_when_disabled(): void
    {
        View::share('noIndex', false);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('<meta name="robots" content="noindex">', false);
    }

    public function test_noindex_config_truthy_shares_true_to_views(): void
    {
        config()->set('externals.no_index', '1');
        $this->app->register(AppServiceProvider::class, force: true);

        $this->assertTrue(View::shared('noIndex'));
    }

    public function test_noindex_config_falsy_shares_false_to_views(): void
    {
        config()->set('externals.no_index', '0');
        $this->app->register(AppServiceProvider::class, force: true);

        $this->assertFalse(View::shared('noIndex'));
    }

    public function test_noindex_defaults_to_false_when_env_var_is_absent(): void
    {
        // Matches the production configuration where GOOGLE_NO_INDEX is not set.
        // The default of `env('GOOGLE_NO_INDEX', false)` resolves to false.
        $config = require config_path('externals.php');

        $this->assertFalse($config['no_index']);
    }
}
