<?php

declare(strict_types=1);

namespace App\Search;

use App\Email\Email;

class ReadOnlySearchIndex implements SearchIndex
{
    public function indexEmail(Email $email): void
    {
        // Intentionally a no-op
    }
}
