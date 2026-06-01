<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\Email;

class ReadOnlySearchIndex implements SearchIndex
{
    public function indexEmail(Email $email): void
    {
        // Intentionally a no-op
    }
}
