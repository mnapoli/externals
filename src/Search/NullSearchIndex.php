<?php declare(strict_types=1);

namespace Externals\Search;

use Externals\Email\Email;

class NullSearchIndex implements SearchIndex
{
    public function indexEmail(Email $email): void
    {
        // No-op implementation for development
    }
}