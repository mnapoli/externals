<?php

declare(strict_types=1);

use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a run can resume from an email, going down to the first one', function (): void {
    foreach ([1, 2, 3] as $number) {
        Email::factory()->create([
            'number' => $number,
            'content' => 'Old content',
            'source' => "From: john@example.com\r\nSubject: Test\r\nContent-Type: text/plain; charset=utf-8\r\n\r\nNew content $number",
        ]);
    }

    $this->artisan('externals:reparse', ['--from' => 2])->assertSuccessful();

    expect(Email::where('number', 3)->value('content'))->toBe('Old content')
        ->and(Email::where('number', 2)->value('content'))->toContain('New content 2')
        ->and(Email::where('number', 1)->value('content'))->toContain('New content 1');
});

test('an email whose text is not valid UTF-8 is saved', function (): void {
    // The email declares UTF-8, but "=8C" is a curly quote in Windows-1252
    Email::factory()->create([
        'number' => 1,
        'source' => "From: john@example.com\r\nSubject: Test\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\ncurry('strpos=8C, arg(1))",
    ]);

    $this->artisan('externals:reparse')->assertSuccessful();

    expect(Email::where('number', 1)->value('content'))->toContain("curry('strpos?, arg(1))");
});
