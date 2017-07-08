<?php
declare(strict_types = 1);

namespace Externals\Search;

use Externals\Email\Email;

class ReadOnlySearchIndex implements SearchIndex
{
    public function indexEmail(Email $email) : void
    {
        // Do nothing, this search index is read only
    }
}
