<?php
declare(strict_types = 1);

namespace Externals\Domain\Thread;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface ThreadRepository
{
    /**
     * @return int|null Thread ID or null if not found.
     */
    public function findBySubject(string $subject);

    public function getSubject(int $id) : string;

    public function create(string $subject) : int;

    public function findLatest() : array;
}
