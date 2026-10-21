<?php

declare(strict_types=1);

use App\Actions\GetOrCreateUser;
use App\Services\Email\AvatarUrls;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the GitHub avatar of people who logged in, Gravatar for the others', function (): void {
    app(GetOrCreateUser::class)->handle('583231', 'octocat', 'octocat@github.com', ['octocat@github.com', 'octocat@php.net']);

    $urls = app(AvatarUrls::class)->of(['Octocat@PHP.net', 'rowan@example.com', null, '']);

    expect($urls)->toBe([
        'Octocat@PHP.net' => 'https://avatars.githubusercontent.com/u/583231?s=64&v=4',
        'rowan@example.com' => 'https://www.gravatar.com/avatar/' . hash('sha256', 'rowan@example.com') . '?s=64&d=blank',
    ]);
});
