<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the stored theme is applied by a script in the head, before the page is painted', function (): void {
    $head = (string) mb_strstr((string) $this->get('/')->assertOk()->getContent(), '</head>', true);

    expect($head)->toContain("localStorage.getItem('theme')");
});

test('the footer offers a light, dark or auto theme, which browsers do not restore on Back', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder([
            'role="radiogroup" aria-labelledby="theme-label"',
            'name="theme" value="light" autocomplete="off"',
            'name="theme" value="dark" autocomplete="off"',
            'name="theme" value="auto" autocomplete="off"',
        ], false);
});

test('every page loads Livewire once, so the theme switch works there', function (string $path, int $status): void {
    $html = (string) $this->get($path)->assertStatus($status)->getContent();

    expect($html)->toContain('role="radiogroup"')
        ->and(preg_match_all('#<script[^>]+livewire(\.min)?\.js#', $html))->toBe(1);
})->with([
    'a page without a Livewire component' => ['/does-not-exist', 404],
    'a Livewire page' => ['/', 200],
]);
