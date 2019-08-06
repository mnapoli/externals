<?php declare(strict_types=1);

namespace Externals\Search;

use Externals\Email\Email;

interface SearchIndex
{
    public function indexEmail(Email $email): void;
}
