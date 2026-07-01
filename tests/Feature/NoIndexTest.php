<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('noindex meta tag is rendered when enabled', function (): void {
    config()->set('externals.no_index', true);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('<meta name="robots" content="noindex">', false);
});

test('noindex meta tag is not rendered when disabled', function (): void {
    config()->set('externals.no_index', false);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertDontSee('<meta name="robots" content="noindex">', false);
});

test('noindex config truthy renders meta tag', function (): void {
    config()->set('externals.no_index', '1');

    $this->get('/')->assertSee('<meta name="robots" content="noindex">', false);
});

test('noindex config falsy does not render meta tag', function (): void {
    config()->set('externals.no_index', '0');

    $this->get('/')->assertDontSee('<meta name="robots" content="noindex">', false);
});

test('noindex defaults to false when env var is absent', function (): void {
    // Matches the production configuration where GOOGLE_NO_INDEX is not set.
    // The default of `env('GOOGLE_NO_INDEX', false)` resolves to false.
    $config = require config_path('externals.php');

    $this->assertFalse($config['no_index']);
});
