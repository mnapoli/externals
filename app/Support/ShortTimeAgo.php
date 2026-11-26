<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

/**
 * The time elapsed since a date in one short unit, e.g. "5m", "3h", "2d", "4mo", "7y", for narrow columns.
 */
final class ShortTimeAgo
{
    public static function of(DateTimeInterface $date): string
    {
        $date = Carbon::instance($date);
        $now = Carbon::now();
        $seconds = max(0, $now->getTimestamp() - $date->getTimestamp());

        return match (true) {
            $seconds < 60 => 'now',
            $seconds < 3600 => intdiv($seconds, 60) . 'm',
            $seconds < 86400 => intdiv($seconds, 3600) . 'h',
            $seconds < 7 * 86400 => intdiv($seconds, 86400) . 'd',
            $date->diffInMonths($now) < 1 => intdiv($seconds, 7 * 86400) . 'w',
            $date->diffInYears($now) < 1 => (int) $date->diffInMonths($now) . 'mo',
            default => (int) $date->diffInYears($now) . 'y',
        };
    }
}
