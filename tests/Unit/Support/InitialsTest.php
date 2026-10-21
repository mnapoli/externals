<?php

declare(strict_types=1);

use App\Support\Initials;

test('initials of a name', function (string $name, string $initials): void {
    expect(Initials::of($name))->toBe($initials);
})->with([
    ['Rowan Tommins [IMSoP]', 'RT'],
    ['Gina P. Banyard', 'GB'],
    ['Jean-Marc Dupont', 'JD'],
    ['tim@bastelstu.be', 'TB'],
    ['Lynn', 'L'],
    ['Édouard Ünal', 'ÉÜ'],
    ['', ''],
    ['[RFC]', ''],
]);
