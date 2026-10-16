<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Shown in place of a photo, e.g. "RT" for "Rowan Tommins [IMSoP]" or "TB" for "tim@bastelstu.be".
 */
final class Initials
{
    public static function of(string $name): string
    {
        // The first and last words, skipping "[IMSoP]", "(PHP)"… Addresses count as names: they are split on @ and dots
        $words = array_values(array_filter(
            preg_split('/[\s.@_-]+/u', mb_trim($name)) ?: [],
            fn(string $word) => preg_match('/^\p{L}/u', $word) === 1,
        ));
        if ($words === []) {
            return '';
        }

        return mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : ''));
    }
}
