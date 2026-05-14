<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\Email;

interface SearchIndex
{
    public function indexEmail(Email $email): void;
}
