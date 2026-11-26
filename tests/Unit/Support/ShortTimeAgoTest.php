<?php

declare(strict_types=1);

use App\Support\ShortTimeAgo;
use Carbon\Carbon;

afterEach(fn() => Carbon::setTestNow());

test('the time elapsed in one short unit', function (string $date, string $expected): void {
    Carbon::setTestNow('2026-11-26 12:00:00');

    expect(ShortTimeAgo::of(new DateTimeImmutable($date)))->toBe($expected);
})->with([
    ['2026-11-26 11:59:30', 'now'],
    ['2026-11-26 11:55:00', '5m'],
    ['2026-11-26 09:00:00', '3h'],
    ['2026-11-24 12:00:00', '2d'],
    ['2026-11-12 12:00:00', '2w'],
    ['2026-07-26 12:00:00', '4mo'],
    ['2019-10-08 12:00:00', '7y'],
    // A date in the future, e.g. a wrong clock in the sender's mail client
    ['2026-11-26 13:00:00', 'now'],
]);
