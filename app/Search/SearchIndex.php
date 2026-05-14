<?php

declare(strict_types=1);

namespace App\Search;

use App\Email\Email;

interface SearchIndex
{
    public function indexEmail(Email $email): void;
}
