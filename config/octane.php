<?php

declare(strict_types=1);

use Laravel\Socialite\Contracts\Factory as SocialiteFactory;

return [

    /*
    |--------------------------------------------------------------------------
    | Warm / Flush Bindings
    |--------------------------------------------------------------------------
    |
    | Only the "flush" list is overridden here; every other Octane setting
    | falls back to the package defaults (merged via mergeConfigFrom).
    |
    | Socialite resolves its manager as a singleton and caches the OAuth
    | driver together with the current request's `code`/`state`. On a warm
    | Octane worker that stale driver leaks into the next login attempt,
    | making the GitHub token exchange fail with "Bad credentials". Flushing
    | the manager between requests forces a fresh driver bound to the current
    | request each time.
    |
    */

    'flush' => [
        SocialiteFactory::class,
    ],

];
